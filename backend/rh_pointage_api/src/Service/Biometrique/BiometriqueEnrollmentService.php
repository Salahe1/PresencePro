<?php

namespace App\Service\Biometrique;

use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;

final class BiometriqueEnrollmentService
{
    private const VERSION = 1;
    private const DESCRIPTOR_SIZE = 128;
    private const MAX_DESCRIPTORS = 5;

    public function __construct( private EntityManagerInterface $entityManager ) { }

    /**
     * @param array<int, array<int, float|int|string>> $descriptors
     */
    public function enrollFromFaceApiDescriptors(Employe $employe, array $descriptors): array
    {
        $normalizedDescriptors = $this->normalizeDescriptors($descriptors);

        $biometriqueData =  [
            'version' => self::VERSION,
            'modality' => 'face',
            'algorithm' => 'face-api.js',
            'descriptorSize' => self::DESCRIPTOR_SIZE,
            'samplesCount' => count($normalizedDescriptors),
            'averageDescriptor' => $this->averageDescriptors($normalizedDescriptors),
            'descriptors' => $normalizedDescriptors,
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $employe->setBiometriqueData($biometriqueData);
        $this->entityManager->flush();

        return $biometriqueData;
    }

    private function normalizeDescriptors(array $descriptors): array
    {
        if ($descriptors === []) {
            throw new \InvalidArgumentException('Au moins un descriptor face-api.js est obligatoire.');
        }

        if (count($descriptors) > self::MAX_DESCRIPTORS) {
            throw new \InvalidArgumentException(sprintf('Le nombre maximum de descriptors est %d.', self::MAX_DESCRIPTORS));
        }

        return array_map(
            fn(mixed $descriptor): array => $this->normalizeDescriptor($descriptor),
            $descriptors
        );
    }

    /**
     * @return array<int, float>
     */
    private function normalizeDescriptor(mixed $descriptor): array
    {
        if (!is_array($descriptor)) {
            throw new \InvalidArgumentException('Chaque descriptor doit etre un tableau de nombres.');
        }

        if (count($descriptor) !== self::DESCRIPTOR_SIZE) {
            throw new \InvalidArgumentException(sprintf('Chaque descriptor face-api.js doit contenir %d valeurs.', self::DESCRIPTOR_SIZE));
        }

        return array_map(
            function (mixed $value): float {
                if (!is_int($value) && !is_float($value) && !is_numeric($value)) {
                    throw new \InvalidArgumentException('Chaque valeur du descriptor doit etre numerique.');
                }

                $floatValue = (float) $value;

                if (!is_finite($floatValue)) {
                    throw new \InvalidArgumentException('Chaque valeur du descriptor doit etre un nombre fini.');
                }

                return round($floatValue, 8);
            },
            array_values($descriptor)
        );
    }

    /**
     * @param array<int, array<int, float>> $descriptors
     * @return array<int, float>
     */
    private function averageDescriptors(array $descriptors): array
    {
        $average = array_fill(0, self::DESCRIPTOR_SIZE, 0.0);

        foreach ($descriptors as $descriptor) {
            foreach ($descriptor as $index => $value) {
                $average[$index] += $value;
            }
        }

        $count = count($descriptors);

        return array_map(
            fn(float $value): float => round($value / $count, 8),
            $average
        );
    }
}
