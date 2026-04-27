<?php

namespace App\MessageHandler;

use App\Message\PlanifierDetectionAbsencesDuJourMessage;
use App\Service\Absence\AbsencePlanningService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PlanifierDetectionAbsencesDuJourHandler
{
    private \DateTimeZone $timeZone;

    public function __construct(
        private AbsencePlanningService $absencePlanningService,
    ) {
        $this->timeZone = new \DateTimeZone('Africa/Casablanca');
    }

    public function __invoke(PlanifierDetectionAbsencesDuJourMessage $message): void
    {
        $today = new \DateTimeImmutable('today', $this->timeZone);

        $this->absencePlanningService->planifierPourDate($today);
    }
}