<?php

namespace App\Service\Absence;

use App\DTO\DetectionAbsenceResult;
use App\Entity\Absence;
use App\Entity\Departement;
use App\Entity\PlageHoraire;
use App\Repository\AbsenceRepository;
use App\Repository\EmployeRepository;
use App\Repository\PointageRepository;
use App\Service\Alerte\AlerteNotifierService;
use App\Service\Calendrier\CalendrierTravailResolverService;
use Doctrine\ORM\EntityManagerInterface;

final class AbsenceDetectionService
{
    public function __construct(
        private EmployeRepository $employeRepository,
        private PointageRepository $pointageRepository,
        private AbsenceRepository $absenceRepository,
        private CalendrierTravailResolverService $calendrierResolver,
        private AlerteNotifierService $alerteNotifierService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function detecterPourDateDepartementEtPlage(
        \DateTimeImmutable $date,
        Departement $departement,
        PlageHoraire $plage
    ): DetectionAbsenceResult {
        $date = $date->setTime(0, 0, 0);
        $result = new DetectionAbsenceResult();

        $horaireTravail = $departement->getHoraireTravail();

        if (!$horaireTravail) {
            return $result;
        }

        if ($plage->getHoraireTravail()?->getId() !== $horaireTravail->getId()) {
            throw new \DomainException('La plage horaire ne correspond pas à l’horaire du département.');
        }

        if (!$this->calendrierResolver->estJourTravaille($date, $departement)) {
            return $result;
        }

        $employes = $this->employeRepository->findActifsByDepartementAndDate($departement, $date);

        $debutPlage = $this->combineDateAndTime($date, $plage->getHeureDebut());
        $finPlage = $this->combineDateAndTime($date, $plage->getHeureFin());

        foreach ($employes as $employe) {
            if ($this->absenceRepository->existsForEmployeDateAndOrdrePlage(
                $employe,
                $date,
                $plage->getOrdre()
            )) {
                $result->incrementIgnored();
                continue;
            }

            if ($this->pointageRepository->hasPointageBetween($employe, $debutPlage, $finPlage)) {
                $result->incrementIgnored();
                continue;
            }

            $absence = new Absence();
            $absence->setEmploye($employe);
            $absence->setDate($date);
            $absence->setOrdrePlage($plage->getOrdre());
            $absence->setHeureDebutPrevue($plage->getHeureDebut());
            $absence->setHeureFinPrevue($plage->getHeureFin());

            $this->entityManager->persist($absence);

            $alerte = $this->alerteNotifierService->declencherAlerteAbsence($absence);
            $this->entityManager->persist($alerte);

            $result->incrementCreated();
        }

        $this->entityManager->flush();

        return $result;
    }

    private function combineDateAndTime(
        \DateTimeImmutable $date,
        \DateTimeImmutable $time
    ): \DateTimeImmutable {
        return $date->setTime(
            (int) $time->format('H'),
            (int) $time->format('i'),
            (int) $time->format('s')
        );
    }
}