<?php

namespace App\Tests\Service\Alerte;

use App\Entity\Absence;
use App\Entity\Employe;
use App\Enum\StatutAlerte;
use App\Enum\TypeAlerte;
use App\Service\Alerte\AlerteNotifierService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\Alerte\AlerteNotifierService
 */
final class AlerteNotifierServiceTest extends TestCase
{

    public function testDeclencheAlerteAbsence(): void
    {
        $employe = new Employe();
        $employe->setMatricule('EMP001');
        $employe->setNom('Dupont');
        $employe->setPrenom('Ali');
        $employe->setEmail('ali.dupont@example.com');
        $employe->setTelephone('0600000000');
        $employe->setPoste('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2024-01-01'));
        $employe->setActif(true);

        $absence = new Absence();
        $absence->setEmploye($employe);
        $absence->setDate(new \DateTimeImmutable('2026-04-10'));

        if (method_exists($absence, 'setOrdrePlage')) {
            $absence->setOrdrePlage(1);
        }

        if (method_exists($absence, 'setHeureDebutPrevue')) {
            $absence->setHeureDebutPrevue(new \DateTimeImmutable('08:00:00'));
        }

        if (method_exists($absence, 'setHeureFinPrevue')) {
            $absence->setHeureFinPrevue(new \DateTimeImmutable('12:00:00'));
        }

        $service = new AlerteNotifierService();

        $alerte = $service->declencherAlerteAbsence($absence);

        $this->assertSame(TypeAlerte::ABSENCE, $alerte->getType());
        $this->assertSame(StatutAlerte::NON_LU, $alerte->getStatut());
        $this->assertSame($absence, $alerte->getAbsence());
        $this->assertNull($alerte->getRetard());

        $this->assertStringContainsString('EMP001', $alerte->getMessage());
        $this->assertStringContainsString('Ali Dupont', $alerte->getMessage());
        $this->assertStringContainsString('2026-04-10', $alerte->getMessage());
    }
}