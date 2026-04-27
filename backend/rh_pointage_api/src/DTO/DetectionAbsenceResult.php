<?php

namespace App\DTO;

final class DetectionAbsenceResult
{
    public function __construct(
        private int $createdCount = 0,
        private int $ignoredCount = 0,
    ) {
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getIgnoredCount(): int
    {
        return $this->ignoredCount;
    }

    public function incrementCreated(): void
    {
        $this->createdCount++;
    }

    public function incrementIgnored(): void
    {
        $this->ignoredCount++;
    }

    public function merge(self $other): self
    {
        return new self(
            $this->createdCount + $other->createdCount,
            $this->ignoredCount + $other->ignoredCount,
        );
    }
}