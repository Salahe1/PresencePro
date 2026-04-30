<?php

namespace App\Controller\Api;

use App\Entity\Absence;
use App\Repository\AbsenceRepository;
use App\Service\Absence\AbsenceQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;



#[Route('/api/absences')]
class AbsenceController extends AbstractController
{
    public function __construct(
        private AbsenceQueryService $absenceQueryService,
        private AbsenceRepository $absenceRepository
    ) {}

    #[Route('', methods: ['GET'])]
    public function absencesList(): JsonResponse
    {
        $absences = $this->absenceRepository->getAllAbsences();

        $responseData = array_map(
            fn(Absence $absence) => $this->serializeAbsence($absence),
            $absences
        );

        return new JsonResponse($responseData);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function absence(int $id): JsonResponse
    {
        $absence = $this->absenceRepository->find($id);

        if (!$absence) {
            return new JsonResponse(['error' => 'Absence non trouvée'], 404);
        }

        return new JsonResponse($this->serializeAbsence($absence));
    }

    #[Route('/employe/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function absencesEmploye(int $id): JsonResponse
    {
        $absences = $this->absenceRepository->getAllAbsencesEmploye($id);

        $responseData = array_map(
            fn(Absence $absence) => $this->serializeAbsence($absence),
            $absences
        );

        return new JsonResponse($responseData);
    }

    #[Route('/date/{date}', methods: ['GET'])]
    public function absencesDate(string $date): JsonResponse
    {
        $absences = $this->absenceQueryService->getAllAbsencesByDate($date);

        $responseData = array_map(
            fn(Absence $absence) => $this->serializeAbsence($absence),
            $absences
        );

        return new JsonResponse($responseData);
    }

    private function serializeAbsence(Absence $absence): array
    {
        $employe = $absence->getEmploye();
        $justification = $absence->getJustification();

        return [
            'id' => $absence->getId(),
            'date' => $absence->getDate()?->format('Y-m-d'),
            'statut' => $absence->getStatut()->value,
            'typeAbsence' => $absence->getTypeAbsence()?->value,
            'ordrePlage' => method_exists($absence, 'getOrdrePlage')
                ? $absence->getOrdrePlage()
                : null,
            'heureDebutPrevue' => method_exists($absence, 'getHeureDebutPrevue') && $absence->getHeureDebutPrevue()
                ? $absence->getHeureDebutPrevue()->format('H:i')
                : null,
            'heureFinPrevue' => method_exists($absence, 'getHeureFinPrevue') && $absence->getHeureFinPrevue()
                ? $absence->getHeureFinPrevue()->format('H:i')
                : null,
            'employe' => $employe ? [
                'id' => $employe->getId(),
                'matricule' => $employe->getMatricule(),
                'nomComplet' => $employe->getNomComplet(),
            ] : null,
            'justification' => $justification ? [
                'id' => $justification->getId(),
                'motif' => $justification->getMotif(),
                'commentaire' => $justification->getCommentaire(),
                'justificatifPath' => $justification->getJustificatifPath(),
                'dateJustification' => $justification->getDateJustification()?->format('Y-m-d'),
            ] : null,
        ];
    }
}