<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $action = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateAction = null;

    #[ORM\Column(length: 100)]
    private ?string $entite = null;

    #[ORM\Column]
    private ?int $entiteId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ancienneValeur = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $nouvelleValeur = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $adresseIP = null;

    #[ORM\ManyToOne(inversedBy: 'auditLogs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int { return $this->id; }
 
    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
 
    public function getDateAction(): ?\DateTimeImmutable  {  return $this->dateAction;}
    public function setDateAction(\DateTimeImmutable $dateAction): static  {  $this->dateAction = $dateAction;  return $this; }
 
    public function getEntite(): ?string { return $this->entite; }
    public function setEntite(string $entite): static { $this->entite = $entite; return $this; }
 
    public function getEntiteId(): ?int { return $this->entiteId; }
    public function setEntiteId(int $entiteId): static { $this->entiteId = $entiteId; return $this; }
 
    public function getAncienneValeur(): ?array { return $this->ancienneValeur; }
    public function setAncienneValeur(?array $ancienneValeur): static { $this->ancienneValeur = $ancienneValeur; return $this; }
 
    public function getNouvelleValeur(): ?array { return $this->nouvelleValeur; }
    public function setNouvelleValeur(?array $nouvelleValeur): static { $this->nouvelleValeur = $nouvelleValeur; return $this; }
 
    public function getAdresseIP(): ?string { return $this->adresseIP; }
    public function setAdresseIP(?string $adresseIP): static { $this->adresseIP = $adresseIP; return $this; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

}
