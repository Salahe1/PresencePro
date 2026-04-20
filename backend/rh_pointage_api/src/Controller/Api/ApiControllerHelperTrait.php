<?php

namespace App\Controller\Api;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;

trait ApiControllerHelperTrait
{
    private function parseJson(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw new ApiException('JSON invalide.', 400, 'INVALID_JSON');
        }
    }

    private function requireFields(array $data, array $fields): void
    {
        $details = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $details[$field] = ['Ce champ est obligatoire.'];
            }
        }

        if (!empty($details)) {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                $details
            );
        }
    }

    private function parseDate(string $value, string $field = 'date', string $formatHint = 'Y-m-d'): \DateTimeImmutable
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
                    $field => ["Format attendu : {$formatHint}"],
                ]
            );
        }

        return $date;
    }

    private function parseDateTime(string $value, string $field = 'timeStamp'): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw new ApiException(
                'Format de date invalide.',
                422,
                'INVALID_DATETIME_FORMAT',
                [
                    $field => ['Format attendu : date/heure ISO valide.'],
                ]
            );
        }
    }

    private function parseTime(string $value, string $field): \DateTimeImmutable
    {
        $time = \DateTimeImmutable::createFromFormat('H:i', $value)
            ?: \DateTimeImmutable::createFromFormat('H:i:s', $value);

        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $time === false ||
            (
                $errors !== false &&
                (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)
            )
        ) {
            throw new ApiException(
                'Format d\'heure invalide.',
                422,
                'INVALID_TIME_FORMAT',
                [
                    $field => ['Format attendu : H:i ou H:i:s'],
                ]
            );
        }

        return $time;
    }

    private function assertFound(mixed $entity, string $message, string $code): void
    {
        if (!$entity) {
            throw new ApiException($message, 404, $code);
        }
    }
}
