<?php

namespace App\Entity;

use App\Repository\AlerteRepository;
use App\Enum\StatutAlerte;
use App\Enum\TypeAlerte;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlerteRepository::class)]
class Alerte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(enumType: StatutAlerte::class)]
    private ?StatutAlerte $statut = StatutAlerte::NON_LU;

    #[ORM\Column(enumType: TypeAlerte::class)]
    private ?TypeAlerte $type = null;

     // The admin who handled this alert (nullable — not yet handled)
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $traitePar = null;
 
    // Source of the alert — only one of these two will be set
    #[ORM\OneToOne(targetEntity: Retard::class, inversedBy: 'alerte')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Retard $retard = null;
 
    #[ORM\OneToOne(targetEntity: Absence::class, inversedBy: 'alerte')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Absence $absence = null;

    public function getId(): ?int {  return $this->id; }

    public function getMessage(): ?string {  return $this->message; }
    public function setMessage(?string $message): static {$this->message = $message; return $this;}

    public function getStatut(): ?StatutAlerte{ return $this->statut; }
    public function setStatut(StatutAlerte $statut): static { $this->statut = $statut; return $this; }

    public function getType(): ?TypeAlerte{ return $this->type; }
    public function setType(TypeAlerte $type): static { $this->type = $type; return $this; }

    public function getTraitePar(): ?Utilisateur { return $this->traitePar; }
    public function setTraitePar(?Utilisateur $traitePar): static { $this->traitePar = $traitePar; return $this; }
 
    public function getRetard(): ?Retard { return $this->retard; }
    public function setRetard(?Retard $retard): static { $this->retard = $retard; return $this; }
 
    public function getAbsence(): ?Absence { return $this->absence; }
    public function setAbsence(?Absence $absence): static { $this->absence = $absence; return $this; }
 
    public function marquerTraitee(Utilisateur $admin): static
    {
        $this->statut    = StatutAlerte::LU;
        $this->traitePar = $admin;
        return $this;
    }
 
    public function archiver(): static
    {
        $this->statut = StatutAlerte::ARCHIVEE;
        return $this;
    }
}
