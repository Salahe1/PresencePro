<?php

namespace App\Entity;

use App\Repository\JustificationAbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JustificationAbsenceRepository::class)]
class JustificationAbsence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatifPath = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateJustification = null;

    #[ORM\OneToOne(targetEntity: Absence::class, inversedBy: 'justification')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Absence $absence = null;
 
    // The RH admin who created this justification
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'justificationsCreees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $saisieParAdmin = null;

    
    public function getId(): ?int { return $this->id; }
 
    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(string $motif): static { $this->motif = $motif; return $this; }
 
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }
 
    public function getJustificatifPath(): ?string { return $this->justificatifPath; }
    public function setJustificatifPath(?string $justificatifPath): static { $this->justificatifPath = $justificatifPath; return $this; }
 
    public function getDateJustification(): ?\DateTimeImmutable  {  return $this->dateJustification; }
    public function setDateJustification(\DateTimeImmutable $dateJustification): static { $this->dateJustification = $dateJustification;  return $this; }

    public function getAbsence(): ?Absence { return $this->absence; }
    public function setAbsence(Absence $absence): static { $this->absence = $absence; return $this; }
 
    public function getSaisieParAdmin(): ?Utilisateur { return $this->saisieParAdmin; }
    public function setSaisieParAdmin(Utilisateur $saisieParAdmin): static { $this->saisieParAdmin = $saisieParAdmin; return $this; }   
}
