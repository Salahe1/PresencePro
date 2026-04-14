<?php

namespace App\Entity;

use App\Repository\TerminalPointageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: TerminalPointageRepository::class)]
class TerminalPointage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $identifiant;

    #[ORM\Column(length:255)]
    private string $secretHash; // hash du secret

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(length:150, nullable: true)]
    private ?string $label = null; // "Tablette Accueil 01"// TAB-ACCUEIL-01

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dernierAccesAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt ;

    public function __construct() {
          $this->identifiant = new Ulid();
          $now = new \DateTimeImmutable(); 
          $this->createdAt = $now;
          $this->updatedAt = $now;
   }
    
    public function getId(): ?int { return $this->id; }

    public function getIdentifiant(): Ulid { return $this->identifiant; }
    public function getIdentifiantAsString(): string  { return (string) $this->identifiant; }

    public function getSecretHash(): string { return $this->secretHash; }
    public function setSecretHash(string $secretHash): self { $this->secretHash = $secretHash;  return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): self { $this->actif = $actif; return $this; }
    public function desactiver(): self { $this->actif = false; return $this; }
    public function activer(): self { $this->actif = true; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }

    public function getDernierAccesAt(): ?\DateTimeImmutable { return $this->dernierAccesAt; }
    public function setDernierAccesAt(?\DateTimeImmutable $dernierAccesAt): self { $this->dernierAccesAt = $dernierAccesAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}