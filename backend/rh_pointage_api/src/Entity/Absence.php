<?php

namespace App\Entity;

use App\Enum\StatutAbsence;
use App\Enum\TypeAbsence;
use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date =null;
 
    #[ORM\Column(enumType: StatutAbsence::class)]
    private StatutAbsence $statut = StatutAbsence::NONJUSTIFIE;
 
    #[ORM\Column(enumType: TypeAbsence::class, nullable: true)]
    private ?TypeAbsence $typeAbsence = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employe $employe = null;
 
    // Set when the admin justifies the absence
    #[ORM\OneToOne(mappedBy: 'absence', targetEntity: JustificationAbsence::class, cascade: ['persist', 'remove'])]
    private ?JustificationAbsence $justification = null;
 
    // One Absence can generate one Alerte
    #[ORM\OneToOne(mappedBy: 'absence', targetEntity: Alerte::class)]
    private ?Alerte $alerte = null;

    public function getId(): ?int { return $this->id; }

    public function getDate(): ?\DateTimeImmutable  {  return $this->date; }
    public function setDate(\DateTimeImmutable $date): static  { $this->date = $date;  return $this; }
 
    public function getStatut(): StatutAbsence { return $this->statut; }
    public function setStatut(StatutAbsence $statut): static { $this->statut = $statut; return $this; }
 
    public function getTypeAbsence(): ?TypeAbsence { return $this->typeAbsence; }
    public function setTypeAbsence(?TypeAbsence $typeAbsence): static { $this->typeAbsence = $typeAbsence; return $this; }

    public function getEmploye(): ?Employe { return $this->employe; }
    public function setEmploye(Employe $employe): static { $this->employe = $employe; return $this; }
 
    public function getJustification(): ?JustificationAbsence { return $this->justification; }
    public function setJustification(?JustificationAbsence $justification): static
    {
        if ($justification !== null && $justification->getAbsence() !== $this) {
            $justification->setAbsence($this);
        }
        $this->justification = $justification;
        return $this;
    }
 
    public function getAlerte(): ?Alerte { return $this->alerte; }
 
    public function isJustifiee(): bool
    {
        return $this->statut === StatutAbsence::JUSTIFIE;
    }
 
    public function justifier(JustificationAbsence $justification): static
    {
        $this->statut = StatutAbsence::JUSTIFIE;
        $this->setJustification($justification);
        return $this;
    }
    
}
