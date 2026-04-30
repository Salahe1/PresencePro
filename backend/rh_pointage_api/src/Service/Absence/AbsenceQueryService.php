<?php

namespace App\Service\Absence;

use App\Repository\AbsenceRepository;





final class AbsenceQueryService
{
    public function __construct(
        private AbsenceRepository $absenceRepository
    ) {}

    public function getAllAbsencesByDate(string $date): array
    {
        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('Format de date invalide. Format attendu : Y-m-d.');
        }

        return $this->absenceRepository->getAllAbsencesByDate($dateObj);
    }
}