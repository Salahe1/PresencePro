<?php

namespace App\Service\Common;

use App\Exception\ApiException;

class DateParserService
{
    public function parseYmd(string $value, string $field = 'date'): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false ||
            (
                $errors !== false &&
                (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)
            )
        ) {
            throw new ApiException(
                'Format de date invalide.',
                422,
                'INVALID_DATE_FORMAT',
                [
                    $field => ['Format attendu : Y-m-d'],
                ]
            );
        }

        return $date;
    }

    public function parseYmdOrToday(?string $value, string $field = 'date'): \DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return new \DateTimeImmutable();
        }

        return $this->parseYmd($value, $field);
    }
}