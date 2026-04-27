<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\ManyToOne(targetEntity: HoraireTravail::class, inversedBy: 'departements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HoraireTravail $horaireTravail = null;

    #[ORM\OneToMany(mappedBy: 'departement', targetEntity: CalendrierTravail::class)]
    private Collection $calendriersTravail;

    #[ORM\OneToMany(mappedBy: 'departement', targetEntity: Employe::class)]
    private Collection $employes;

    public function __construct()
    {
        $this->employes = new ArrayCollection();
        $this->calendriersTravail = new ArrayCollection();
    }

    public function getId(): ?int {  return $this->id; }

    public function getLabel(): ?string { return $this->label; }

    public function setLabel(?string $label): static {  $this->label = $label;  return $this; }

    public function getHoraireTravail(): ?HoraireTravail { return $this->horaireTravail; }

    public function setHoraireTravail(?HoraireTravail $horaireTravail): static { $this->horaireTravail = $horaireTravail; return $this; }

    public function getCalendriersTravail(): Collection {  return $this->calendriersTravail; }

    public function getEmployes(): Collection { return $this->employes; }
}
