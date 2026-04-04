<?php

namespace App\Entity;

use App\Repository\HoraireTravailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoraireTravailRepository::class)]
class HoraireTravail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $toleranceRetard = null;

    #[ORM\OneToMany(mappedBy: 'horaireTravail', targetEntity: PlageHoraire::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $plagesHoraires;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'horairesTravail')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Departement $departement = null;

    public function __construct()
    {
        $this->plagesHoraires = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id;  }

    public function getLabel(): ?string   {  return $this->label;  }
    public function setLabel(?string $label): static  {  $this->label = $label;  return $this; }

    public function getToleranceRetard(): ?\DateTimeImmutable  {  return $this->toleranceRetard; }
    public function setToleranceRetard(\DateTimeImmutable $toleranceRetard): static  {  $this->toleranceRetard = $toleranceRetard;  return $this; }

    //@return Collection<int, PlageHoraire> */
    public function getPlagesHoraires(): Collection {  return $this->plagesHoraires;}

    public function addPlageHoraire(PlageHoraire $plageHoraire): static
    {
        if (!$this->plagesHoraires->contains($plageHoraire)) {
            $this->plagesHoraires->add($plageHoraire);
            $plageHoraire->setHoraireTravail($this);
        }

        return $this;
    }

    public function removePlageHoraire(PlageHoraire $plageHoraire): static
    {
        if ($this->plagesHoraires->removeElement($plageHoraire)) {
            if ($plageHoraire->getHoraireTravail() === $this) {
                $plageHoraire->setHoraireTravail(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?Departement { return $this->departement; }
    public function setDepartement(?Departement $departement): static { $this->departement = $departement; return $this; }
    
    // Returns the first PlageHoraire's start time — used by DetectionRetardService
    public function getHeureDebutPremierePlage(): ?\DateTimeInterface
    {
        $first = $this->plagesHoraires->first();
        return $first ? $first->getHeureDebut() : null;
    }
}
