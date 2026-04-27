<?php

namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use App\Enum\TypeJour;
use App\Repository\CalendrierTravailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendrierTravailRepository::class)]
class CalendrierTravail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)] 
    private ?\DateTimeImmutable $dateJour = null;

    #[ORM\Column(enumType: TypeJour::class)]
    private ?TypeJour $typeJour = null;
 
    #[ORM\Column]
    private bool $estTravaille = true;
 
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'calendriersTravail')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Departement $departement = null;

    public function getId(): ?int{ return $this->id;}

    public function getDateJour(): ?\DateTimeImmutable { return $this->dateJour; } 
    public function setDateJour(?\DateTimeImmutable $dateJour): static { $this->dateJour = $dateJour; return $this; }

    public function getTypeJour(): ?TypeJour { return $this->typeJour; }
    public function setTypeJour(TypeJour $typeJour): static { $this->typeJour = $typeJour; return $this; }
 
    public function isEstTravaille(): bool { return $this->estTravaille; }
    public function setEstTravaille(bool $estTravaille): static { $this->estTravaille = $estTravaille; return $this; }
 
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDepartement(): ?Departement { return $this->departement; }
    public function setDepartement(?Departement $departement): static { $this->departement = $departement; return $this; }
}
