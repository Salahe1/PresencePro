<?php

namespace App\Message;

final class DetecterAbsencePlageMessage
{
    public function __construct(
        public readonly string $date,         // format Y-m-d
        public readonly int $departementId,
        public readonly int $plageId,
        public readonly string $plannedEndAt, // format DATE_ATOM
    ) {
    }
}