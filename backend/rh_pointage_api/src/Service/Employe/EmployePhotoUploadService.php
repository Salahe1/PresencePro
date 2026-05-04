<?php

namespace App\Service\Employe;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class EmployePhotoUploadService
{
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private string $employesPhotosDirectory
    ) {}

    public function upload(UploadedFile $file): string
    {
        if (!$file->isValid()) { throw new \InvalidArgumentException('Image invalide.'); }

        if ($file->getSize() === null || $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('La photo ne doit pas dépasser 2 Mo.');
        }

        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Format photo non autorisé. Formats acceptés : JPG, PNG, WEBP.');
        }

        if (!is_dir($this->employesPhotosDirectory)) { mkdir($this->employesPhotosDirectory, 0775, true); }

        $extension = $file->guessExtension() ?: 'jpg';

        $filename = sprintf('employe_photo_%s.%s',bin2hex(random_bytes(16)),$extension);

        $file->move($this->employesPhotosDirectory, $filename);

        return $filename;
    }
}
