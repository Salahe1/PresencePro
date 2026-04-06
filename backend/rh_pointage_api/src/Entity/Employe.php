<?php

namespace App\Entity;

use App\Repository\EmployeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type_employe', type: 'string')]
#[ORM\DiscriminatorMap(['employe' => Employe::class, 'utilisateur' => Utilisateur::class])]
class Employe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateEmbauche = null;

    #[ORM\Column(length: 100)]
    private ?string $poste = null;

    #[ORM\Column]
    private ?bool $actif = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;


    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $biometriqueData = [];

    #[ORM\ManyToOne(inversedBy: 'employes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $departement = null;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Pointage::class, orphanRemoval: true)]
    private Collection $pointages;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Absence::class, orphanRemoval: true)]
    private Collection $absences;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Retard::class, orphanRemoval: true)]
    private Collection $retards;

    public function __construct()
    {
        $this->pointages = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->retards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }
    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }
    public function setPoste(string $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }
    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeImmutable
    {
        return $this->dateEmbauche;
    }
    public function setDateEmbauche(\DateTimeImmutable $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }
    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }
    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getBiometriqueData(): ?array
    {
        return $this->biometriqueData;
    }
    public function setBiometriqueData(?array $biometriqueData): static
    {
        $this->biometriqueData = $biometriqueData;
        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }
    public function setDepartement(?Departement $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    public function getPointages(): Collection
    {
        return $this->pointages;
    }
    public function addPointage(Pointage $pointage): static
    {
        if (!$this->pointages->contains($pointage)) {
            $this->pointages->add($pointage);
            $pointage->setEmploye($this);
        }
        return $this;
    }
    public function removePointage(Pointage $pointage): static
    {
        $this->pointages->removeElement($pointage);
        return $this;
    }

    public function getAbsences(): Collection
    {
        return $this->absences;
    }
    public function addAbsence(Absence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setEmploye($this);
        }
        return $this;
    }
    public function removeAbsence(Absence $absence): static
    {
        $this->absences->removeElement($absence);
        return $this;
    }

    public function getRetards(): Collection
    {
        return $this->retards;
    }
    public function addRetard(Retard $retard): static
    {
        if (!$this->retards->contains($retard)) {
            $this->retards->add($retard);
            $retard->setEmploye($this);
        }
        return $this;
    }
    public function removeRetard(Retard $retard): static
    {
        $this->retards->removeElement($retard);
        return $this;
    }

}
