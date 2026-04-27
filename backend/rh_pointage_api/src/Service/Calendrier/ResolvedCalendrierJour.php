<?php

namespace App\Service\Calendrier;

use App\Entity\CalendrierTravail;
use App\Enum\TypeJour;

final class ResolvedCalendrierJour
{
    public function __construct(
        private \DateTimeImmutable $dateJour,
        private TypeJour $typeJour,
        private bool $estTravaille,
        private string $source, // departement | global | implicit_default
        private ?CalendrierTravail $sourceEntity = null,
    ) {}

    public function getDateJour(): \DateTimeImmutable
    {
        return $this->dateJour;
    }

    public function getTypeJour(): TypeJour { return $this->typeJour; }

    public function isEstTravaille(): bool { return $this->estTravaille; }

    public function getSource(): string { return $this->source; }

    public function getSourceEntity(): ?CalendrierTravail { return $this->sourceEntity; }

    public function isFromDepartementRule(): bool { return $this->source === 'departement'; }

    public function isFromGlobalRule(): bool { return $this->source === 'global'; }

    public function isImplicitDefault(): bool { return $this->source === 'implicit_default'; }
}