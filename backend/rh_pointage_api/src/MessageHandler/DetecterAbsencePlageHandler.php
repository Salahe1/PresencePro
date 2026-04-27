<?php

namespace App\MessageHandler;

use App\Message\DetecterAbsencePlageMessage;
use App\Repository\DepartementRepository;
use App\Repository\PlageHoraireRepository;
use App\Service\Absence\AbsenceDetectionService;
use App\Service\Calendrier\CalendrierTravailResolverService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DetecterAbsencePlageHandler
{
    public function __construct(
        private DepartementRepository $departementRepository,
        private PlageHoraireRepository $plageHoraireRepository,
        private CalendrierTravailResolverService $calendrierResolver,
        private AbsenceDetectionService $absenceDetectionService,
    ) {
    }

    public function __invoke(DetecterAbsencePlageMessage $message): void
    {
        $departement = $this->departementRepository->find($message->departementId);

        if (!$departement) {
            return;
        }

        $plage = $this->plageHoraireRepository->find($message->plageId);

        if (!$plage) {
            return;
        }

        $date = new \DateTimeImmutable($message->date);

        $horaireTravail = $departement->getHoraireTravail();

        if (!$horaireTravail) {
            return;
        }

        if ($plage->getHoraireTravail()?->getId() !== $horaireTravail->getId()) {
            return;
        }

        if (!$this->calendrierResolver->estJourTravaille($date, $departement)) {
            return;
        }

        $this->absenceDetectionService->detecterPourDateDepartementEtPlage(
            $date,
            $departement,
            $plage
        );
    }
}