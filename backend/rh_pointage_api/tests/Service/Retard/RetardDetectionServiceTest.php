<?php

namespace App\Tests\Service\Retard;

use App\Entity\Departement;
use App\Entity\Employe;
use App\Entity\HoraireTravail;
use App\Entity\PlageHoraire;
use App\Entity\Pointage;
use App\Entity\Retard;
use App\Enum\TypePointage;
use App\Service\Retard\RetardDetectionService;
use PHPUnit\Framework\TestCase;


final class RetardDetectionServiceTest extends TestCase
{
    private RetardDetectionService $service;

    protected function setUp(): void
    {
        $this->service = new RetardDetectionService();
    }

    /**
    * @covers \App\Service\Retard\RetardDetectionService::creeRetardSiExiste
    */
    public function testDetecteRetardQuandPointageEnAvance(): void
    {
        $employe = $this->createEmploye();

        $pointage = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 07:45:00'
        );

        $result = $this->service->creeRetardSiExiste($pointage);

        $this->assertNull($result);
    }

    /**
    * @covers \App\Service\Retard\RetardDetectionService::creeRetardSiExiste
    */
    public function testDetecteRetardQuandPointageAuTemps(): void
    {
        $employe = $this->createEmploye();

        $pointage = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 08:00:00'
        );

        $result = $this->service->creeRetardSiExiste($pointage);

        $this->assertNull($result);
    }

    /**
    * @covers \App\Service\Retard\RetardDetectionService::creeRetardSiExiste
    */
    public function testDetecteRetardQuandPointageEnRetard(): void
    {
        $employe = $this->createEmploye();

        $pointage = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 08:20:00'
        );

        $result = $this->service->creeRetardSiExiste($pointage);

       // $this->assertNotNull($result);
       $this->assertInstanceOf(Retard::class, $result);

    }


    /**
    * @covers \App\Service\Retard\RetardDetectionService::creeRetardSiExiste
    */
    public function testDetecteRetardQuandPointageEnRetardMaisDansTolerenceSeuil(): void
    {
        $employe = $this->createEmploye();

        $pointage = $this->createPointage(
            $employe,
            TypePointage::Entre,
            '2026-04-10 08:14:00'
        );

        $result = $this->service->creeRetardSiExiste($pointage);

        $this->assertNull($result);
    }    

    /**
    * @covers \App\Service\Retard\RetardDetectionService::creeRetardSiExiste
    */
    public function testDetecteRetardQuandPointageEstSortie(): void
    {
        $employe = $this->createEmploye();

        $pointage = $this->createPointage(
            $employe,
            TypePointage::Sortie,
            '2026-04-10 17:00:00'
        );

        $result = $this->service->creeRetardSiExiste($pointage);

        $this->assertNull($result);
    }




    private function createEmploye(): Employe {

        $departement = new Departement();
        $departement->setLabel('informatique');

        $plageHoraire1 = new PlageHoraire();
        $plageHoraire1->setHeureDebut(new \DateTimeImmutable('08:00:00'));
        $plageHoraire1->setHeureFin(new \DateTimeImmutable('12:00:00'));
        $plageHoraire1->setOrdre(1);

        $plageHoraire2 = new PlageHoraire();
        $plageHoraire2->setHeureDebut(new \DateTimeImmutable('13:00:00'));
        $plageHoraire2->setHeureFin(new \DateTimeImmutable('17:00:00'));
        $plageHoraire2->setOrdre(2);

        $horaireTravail = new HoraireTravail();
        $horaireTravail->setLabel('Horaire standard');
        $horaireTravail->setToleranceRetard(new \DateTimeImmutable('00:15:00'));

        $horaireTravail->addPlageHoraire($plageHoraire1);
        $horaireTravail->addPlageHoraire($plageHoraire2);

        $horaireTravail->setDepartement($departement);
        $departement->getHorairesTravail()->add($horaireTravail);

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

    private function createPointage(Employe $employe, TypePointage $type, string $dateTime): Pointage
    {
        $pointage = new Pointage();
        $pointage->setEmploye($employe);
        $pointage->setType($type);
        $pointage->setTimeStamp(new \DateTimeImmutable($dateTime));

        return $pointage;
    }
}
