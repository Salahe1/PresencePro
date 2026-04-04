<?php

namespace App\Entity;

use App\Repository\PlageHoraireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlageHoraireRepository::class)]
class PlageHoraire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureDebut = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureFin = null;

    // Ordering within the schedule (1 = morning, 2 = afternoon, etc.)
    #[ORM\Column]
    private ?int $ordre = null;
 
    #[ORM\ManyToOne(targetEntity: HoraireTravail::class, inversedBy: 'plagesHoraires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HoraireTravail $horaireTravail = null;

    public function getId(): ?int { return $this->id; }

    public function getHeureDebut(): ?\DateTimeImmutable { return $this->heureDebut; }
    public function setHeureDebut(\DateTimeImmutable $heureDebut): static  {  $this->heureDebut = $heureDebut; return $this;  }

    public function getHeureFin(): ?\DateTimeImmutable  {  return $this->heureFin; }
    public function setHeureFin(\DateTimeImmutable $heureFin): static  {  $this->heureFin = $heureFin; return $this; }
 
    public function getOrdre(): ?int { return $this->ordre; }
    public function setOrdre(int $ordre): static { $this->ordre = $ordre; return $this; }

    public function getHoraireTravail(): ?HoraireTravail { return $this->horaireTravail; }
    public function setHoraireTravail(?HoraireTravail $horaireTravail): static { $this->horaireTravail = $horaireTravail; return $this; }
}
