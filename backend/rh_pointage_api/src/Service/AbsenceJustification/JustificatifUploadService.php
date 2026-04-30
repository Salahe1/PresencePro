<?php

namespace App\Service\AbsenceJustification;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class JustificatifUploadService
{
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function __construct(
        private string $justificatifsDirectory
    ) {}

    public function upload(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Fichier invalide.');
        }

        if ($file->getSize() === null || $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('Le fichier ne doit pas dépasser 5 Mo.');
        }

        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Format de fichier non autorisé. Formats acceptés : PDF, JPG, PNG.');
        }

        $extension = $file->guessExtension();

        if (!$extension) {
            throw new \InvalidArgumentException('Impossible de déterminer l’extension du fichier.');
        }

        if (!is_dir($this->justificatifsDirectory)) {
            mkdir($this->justificatifsDirectory, 0775, true);
        }

        $filename = sprintf(
            'justificatif_%s.%s',
            bin2hex(random_bytes(16)),
            $extension
        );

        $file->move($this->justificatifsDirectory, $filename);

        return $filename;
    }

    public function delete(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = $this->justificatifsDirectory . DIRECTORY_SEPARATOR . basename($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function getAbsolutePath(?string $filename): string
    {
        if (!$filename) {
            throw new \RuntimeException('JUSTIFICATIF_NOT_FOUND');
        }

        $safeFilename = basename($filename);
        $path = $this->justificatifsDirectory . DIRECTORY_SEPARATOR . $safeFilename;

        if (!is_file($path)) {
            throw new \RuntimeException('JUSTIFICATIF_FILE_NOT_FOUND');
        }

        return $path;
    }
}