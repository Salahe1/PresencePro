<?php

namespace App\Entity;

use App\Enum\TypePointage;
use App\Repository\PointageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointageRepository::class)]
class Pointage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $timeStamp = null;

    #[ORM\Column(enumType: TypePointage::class)]
    private ?TypePointage $type = null;
    
    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'pointages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employe $employe = null;
 
    // A Pointage (ENTREE) may generate one Retard — managed from the Retard side
    #[ORM\OneToOne(mappedBy: 'pointage', targetEntity: Retard::class)]
    private ?Retard $retard = null;

    public function getId(): ?int{return $this->id;}

    public function getTimeStamp(): ?\DateTimeImmutable { return $this->timeStamp; }
    public function setTimeStamp(\DateTimeImmutable $timeStamp): static  { $this->timeStamp = $timeStamp;  return $this; }

    public function getType(): ?TypePointage { return $this->type; }
    public function setType(TypePointage $type): static { $this->type = $type; return $this; }

    public function getEmploye(): Employe { return $this->employe; }
    public function setEmploye(Employe $employe): static { $this->employe = $employe; return $this; }
 
    public function getRetard(): ?Retard { return $this->retard; }
    public function setRetard(?Retard $retard): static
    {
        if ($retard !== null && $retard->getPointage() !== $this) {
            $retard->setPointage($this);
        }

        $this->retard = $retard;

        return $this;
    }
 
    public function isEntree(): bool { return $this->type === TypePointage::Entre;}
    public function isSortie(): bool { return $this->type === TypePointage::Sortie;}
}
