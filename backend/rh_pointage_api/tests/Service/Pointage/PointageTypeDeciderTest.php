<?php

namespace App\Tests\Service\Pointage;

use App\Entity\Departement;
use App\Entity\Employe;
use App\Entity\Pointage;
use App\Enum\TypePointage;
use App\Service\Pointage\PointageTypeDecider;
use PHPUnit\Framework\TestCase;

final class PointageTypeDeciderTest extends TestCase
{
    private PointageTypeDecider $decider;

    protected function setUp(): void
    {
        $this->decider = new PointageTypeDecider();
    }

/**
 * @covers \App\Service\Pointage\PointageTypeDecider::deciderType
 */    public function testRetourneEntreeQuandAucunPointageDuJour(): void
    {
        $employe = $this->createEmploye();
        $scanAt = new \DateTimeImmutable('2026-04-10 08:00:00');

        $result = $this->decider->deciderType($employe, $scanAt);

        $this->assertSame(TypePointage::Entre, $result);
    }

    /**
    * @covers \App\Service\Pointage\PointageTypeDecider::deciderType
    */
    public function testRetourneSortieQuandDernierPointageDuJourEstEntree(): void
    {
        $employe = $this->createEmploye();

        $pointageEntree = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 08:00:00'
        );

        $employe->addPointage($pointageEntree);

        $scanAt = new \DateTimeImmutable('2026-04-10 12:30:00');

        $result = $this->decider->deciderType($employe, $scanAt);

        $this->assertSame(TypePointage::Sortie, $result);
    }

    /**
 * @covers \App\Service\Pointage\PointageTypeDecider::deciderType
 */
    public function testRetourneEntreeQuandDernierPointageDuJourEstSortie(): void
    {
        $employe = $this->createEmploye();

        $pointageEntree = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 08:00:00'
        );

        $pointageSortie = $this->createPointage(
            $employe,
            TypePointage::Sortie,
            '2026-04-10 12:00:00'
        );

        $employe->addPointage($pointageEntree);
        $employe->addPointage($pointageSortie);

        $scanAt = new \DateTimeImmutable('2026-04-10 13:30:00');

        $result = $this->decider->deciderType($employe, $scanAt);

        $this->assertSame(TypePointage::Entre, $result);
    }

 

    private function createEmploye(): Employe
    {
        $departement = new Departement();
        $departement->setLabel('RH');

        $employe = new Employe();
        $employe->setMatricule('EMP001');
        $employe->setNom('Dupont');
        $employe->setPrenom('Ali');
        $employe->setEmail('ali.dupont@example.com');
        $employe->setTelephone('0600000000');
        $employe->setPoste('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2024-01-01'));
        $employe->setActif(true);
        $employe->setDepartement($departement);

        return $employe;
    }

    private function createPointage(
        Employe $employe,
        TypePointage $type,
        string $dateTime
    ): Pointage {
        $pointage = new Pointage();
        $pointage->setEmploye($employe);
        $pointage->setType($type);
        $pointage->setTimeStamp(new \DateTimeImmutable($dateTime));

        return $pointage;
    }
}