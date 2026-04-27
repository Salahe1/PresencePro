<?php

namespace App\Entity;

use App\Repository\RetardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RetardRepository::class)]
class Retard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateJour = null;

    // Expected arrival time from the employee's schedule
    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heurePrevue = null;

    // Actual arrival time from the scan
    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureArrivee = null;
 
    #[ORM\Column]
    private bool $justifie = false;
 
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

     // The ENTREE Pointage that triggered this lateness record
    #[ORM\OneToOne(targetEntity: Pointage::class, inversedBy: 'retard')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pointage $pointage = null;
 
    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'retards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employe $employe = null;
 
    // One Retard can generate one Alerte
    #[ORM\OneToOne(mappedBy: 'retard', targetEntity: Alerte::class)]
    private ?Alerte $alerte = null;

    public function getId(): ?int{ return $this->id;}

    public function getDateJour(): ?\DateTimeImmutable  {  return $this->dateJour;  }
    public function setDateJour(\DateTimeImmutable $dateJour): static  {  $this->dateJour = $dateJour;  return $this; }

    public function getHeurePrevue(): ?\DateTimeImmutable { return $this->heurePrevue; }
    public function setHeurePrevue(\DateTimeImmutable $heurePrevue): static  { $this->heurePrevue = $heurePrevue;  return $this; }

    public function getHeureArrivee(): ?\DateTimeImmutable { return $this->heureArrivee; }
    public function setHeureArrivee(\DateTimeImmutable $heureArrivee): static {  $this->heureArrivee = $heureArrivee;  return $this; }
 
    public function isJustifie(): bool { return $this->justifie; }
    public function setJustifie(bool $justifie): static { $this->justifie = $justifie; return $this; }
 
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getPointage(): ?Pointage { return $this->pointage; }
    public function setPointage(Pointage $pointage): static
    {
        $this->pointage = $pointage;

        if ($pointage->getRetard() !== $this) {  $pointage->setRetard($this); }

        return $this;
    }
 
    public function getEmploye(): Employe { return $this->employe; }
    public function setEmploye(Employe $employe): static { $this->employe = $employe; return $this; }
 
    public function getAlerte(): ?Alerte { return $this->alerte; }


    // Returns the number of minutes late
    public function getDureeRetardMinutes(): int
    {
        if (!$this->heurePrevue || !$this->heureArrivee) {
            return 0;
        }
        $diffMinutes = (int) (($this->heureArrivee->getTimestamp() - $this->heurePrevue->getTimestamp()) / 60);
        return max(0, $diffMinutes);
    }
}
