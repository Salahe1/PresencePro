<?php

namespace App\Service\Absence;

use App\Message\DetecterAbsencePlageMessage;
use App\Repository\DepartementRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final class AbsencePlanningService
{
    private \DateTimeZone $timeZone;

    public function __construct(
        private DepartementRepository $departementRepository,
        private MessageBusInterface $bus,
    ) {
        $this->timeZone = new \DateTimeZone('Africa/Casablanca');
    }

    public function planifierPourDate(\DateTimeImmutable $date): int
    {
        $plannedCount = 0;
        $now = new \DateTimeImmutable('now', $this->timeZone);
        $date = $date->setTimezone($this->timeZone)->setTime(0, 0, 0);

        $departements = $this->departementRepository->findAll();

        foreach ($departements as $departement) {

            $horaireTravail = $departement->getHoraireTravail();
            if (!$horaireTravail) {
                continue;
            }

            foreach ($horaireTravail->getPlagesHoraires() as $plage) {
                $finPlage = $date->setTime(
                    (int) $plage->getHeureFin()->format('H'),
                    (int) $plage->getHeureFin()->format('i'),
                    (int) $plage->getHeureFin()->format('s')
                );

                $delayMs = max(0, ($finPlage->getTimestamp() - $now->getTimestamp()) * 1000);

                $this->bus->dispatch(
                    new DetecterAbsencePlageMessage(
                        $date->format('Y-m-d'),
                        $departement->getId(),
                        $plage->getId(),
                        $finPlage->format(\DateTimeInterface::ATOM)
                    ),
                    [new DelayStamp($delayMs)]
                );

                $plannedCount++;
            }
        }

        return $plannedCount;
    }
}