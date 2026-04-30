<?php

namespace App\Service\AbsenceJustification;

use App\Entity\Absence;
use App\Entity\JustificationAbsence;
use App\Entity\Utilisateur;
use App\Enum\StatutAbsence;
use App\Enum\TypeAbsence;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AbsenceJustificationService
{
    public function __construct(
        private AbsenceRepository $absenceRepository,
        private EntityManagerInterface $entityManager,
        private JustificatifUploadService $justificatifUploadService

    ) {}

    public function justifier(int $absenceId, array $data, Utilisateur $admin): Absence
    {
        $absence = $this->absenceRepository->find($absenceId);

        if (!$absence) {
            throw new \RuntimeException('ABSENCE_NOT_FOUND');
        }

        if ($absence->getJustification() !== null || $absence->isJustifiee()) {
            throw new \LogicException('ABSENCE_ALREADY_JUSTIFIED');
        }

        $motif = trim((string) ($data['motif'] ?? ''));

        if ($motif === '') {
            throw new \InvalidArgumentException('Le motif est obligatoire.');
        }

        $typeAbsenceValue = $data['typeAbsence'] ?? null;

        if (!is_string($typeAbsenceValue) || trim($typeAbsenceValue) === '') {
            throw new \InvalidArgumentException('Le type d’absence est obligatoire.');
        }

        $typeAbsence = TypeAbsence::tryFrom($typeAbsenceValue);

        if (!$typeAbsence) {
            throw new \InvalidArgumentException('Type d’absence invalide.');
        }

        $commentaire = isset($data['commentaire'])
            ? trim((string) $data['commentaire'])
            : null;

        $justificatifPath = isset($data['justificatifPath'])
            ? trim((string) $data['justificatifPath'])
            : null;

        $justification = new JustificationAbsence();
        $justification->setMotif($motif);
        $justification->setCommentaire($commentaire !== '' ? $commentaire : null);
        $justification->setJustificatifPath($justificatifPath !== '' ? $justificatifPath : null);
        $justification->setDateJustification(new \DateTimeImmutable());
        $justification->setSaisieParAdmin($admin);

        $absence->setTypeAbsence($typeAbsence);
        $absence->setStatut(StatutAbsence::JUSTIFIE);
        $absence->setJustification($justification);

        $this->entityManager->persist($justification);
        $this->entityManager->flush();

        return $absence;
    }
    public function modifierJustification(int $absenceId, array $data): Absence
    {
        $absence = $this->absenceRepository->find($absenceId);

        if (!$absence) { throw new \RuntimeException('ABSENCE_NOT_FOUND'); }

        $justification = $absence->getJustification();

        if (!$justification) {  throw new \LogicException('JUSTIFICATION_NOT_FOUND'); }

        if (array_key_exists('motif', $data)) {
            $motif = trim((string) $data['motif']);

            if ($motif === '') { throw new \InvalidArgumentException('Le motif ne peut pas être vide.'); }

            $justification->setMotif($motif);
        }

        if (array_key_exists('commentaire', $data)) {
            $commentaire = trim((string) $data['commentaire']);
            $justification->setCommentaire($commentaire !== '' ? $commentaire : null);
        }

        if (array_key_exists('justificatifPath', $data)) {
            $justificatifPath = trim((string) $data['justificatifPath']);
            $justification->setJustificatifPath($justificatifPath !== '' ? $justificatifPath : null);
        }

        if (array_key_exists('typeAbsence', $data)) {
            $typeAbsenceValue = trim((string) $data['typeAbsence']);

            if ($typeAbsenceValue === '') { throw new \InvalidArgumentException('Le type d’absence ne peut pas être vide.'); }

            $typeAbsence = TypeAbsence::tryFrom($typeAbsenceValue);

            if (!$typeAbsence) { throw new \InvalidArgumentException('Type d’absence invalide.'); }

            $absence->setTypeAbsence($typeAbsence);
        }

        $absence->setStatut(StatutAbsence::JUSTIFIE);
        $this->entityManager->flush();

        return $absence;
    }

    public function supprimerJustification(int $absenceId): void
    {
        $absence = $this->absenceRepository->find($absenceId);

        if (!$absence) {
            throw new \RuntimeException('ABSENCE_NOT_FOUND');
        }

        $justification = $absence->getJustification();

        if (!$justification) {
            throw new \LogicException('JUSTIFICATION_NOT_FOUND');
        }

        $fichier = $justification->getJustificatifPath();

        $absence->setJustification(null);
        $absence->setStatut(StatutAbsence::NONJUSTIFIE);
        $absence->setTypeAbsence(null);

        $this->entityManager->remove($justification);
        $this->entityManager->flush();

        $this->justificatifUploadService->delete($fichier);
    }

    public function uploaderJustificatif(int $absenceId, UploadedFile $file): Absence
    {
        $absence = $this->absenceRepository->find($absenceId);

        if (!$absence) {
            throw new \RuntimeException('ABSENCE_NOT_FOUND');
        }

        $justification = $absence->getJustification();

        if (!$justification) {
            throw new \LogicException('JUSTIFICATION_NOT_FOUND');
        }

        $ancienFichier = $justification->getJustificatifPath();

        $nouveauFichier = $this->justificatifUploadService->upload($file);

        $justification->setJustificatifPath($nouveauFichier);

        $this->entityManager->flush();

        if ($ancienFichier) {
            $this->justificatifUploadService->delete($ancienFichier);
        }

        return $absence;
    }

    public function getJustificatifPath(int $absenceId): string
    {
        $absence = $this->absenceRepository->find($absenceId);

        if (!$absence) {
            throw new \RuntimeException('ABSENCE_NOT_FOUND');
        }

        $justification = $absence->getJustification();

        if (!$justification) {
         throw new \LogicException('JUSTIFICATION_NOT_FOUND');
     }

        return $this->justificatifUploadService->getAbsolutePath(
            $justification->getJustificatifPath()
     );
    }
}