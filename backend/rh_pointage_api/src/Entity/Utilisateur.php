<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use App\Enum\RoleUtilisateur;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur extends Employe implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motDePasse = null;

    #[ORM\Column(enumType: RoleUtilisateur::class)]
    private ?RoleUtilisateur $role = null;

    #[ORM\OneToMany(mappedBy: 'traitePar', targetEntity: Alerte::class)]
    private Collection $alertes;
 
    #[ORM\OneToMany(mappedBy: 'saisieParAdmin', targetEntity: JustificationAbsence::class)]
    private Collection $justificationsCreees;
 
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: AuditLog::class)]
    private Collection $auditLogs;
 
    public function __construct()
    {
        parent::__construct();
        $this->alertes             = new ArrayCollection();
        $this->justificationsCreees = new ArrayCollection();
        $this->auditLogs           = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->getEmail();
    }
 
    public function getRoles(): array
    {
        $roles = [];
        
        if ($this->role) {
            $roles[] = 'ROLE_' . strtoupper($this->role->value);
        }

        return array_unique($roles);
    }


    public function getPassword(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): static { $this->motDePasse = $motDePasse; return $this; }
 
    public function getRole(): ?RoleUtilisateur { return $this->role; }
    public function setRole(RoleUtilisateur $role): static { $this->role = $role; return $this; }

    public function eraseCredentials(): void
    {
    // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getAlertes(): Collection { return $this->alertes; }

    public function getJustificationsCreees(): Collection { return $this->justificationsCreees; }
    public function addJustificationCreee(JustificationAbsence $justification): static
    {
        if (!$this->justificationsCreees->contains($justification)) {
            $this->justificationsCreees->add($justification);
            $justification->setSaisieParAdmin($this);
        }
        return $this;
    }
    public function removeJustificationCreee(JustificationAbsence $justification): static
    {
        // $this->justificationsCreees->removeElement($justification);
        // Note: JustificationAbsence requires an admin (nullable: false).
        // Cannot set saisieParAdmin to null. Handle deletion via cascade / orphanRemoval if needed.
        if ($this->justificationsCreees->removeElement($justification)) {
            // set the owning side to null (unless already changed)
            // if ($justification->getSaisieParAdmin() === $this) {
            //     $justification->setSaisieParAdmin(null);
            // }
        }
        return $this;
    }

    public function getAuditLogs(): Collection { return $this->auditLogs; }


}
