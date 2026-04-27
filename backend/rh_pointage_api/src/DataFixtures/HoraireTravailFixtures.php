<?php

namespace App\DataFixtures;

use App\Entity\HoraireTravail;
use App\Entity\PlageHoraire;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class HoraireTravailFixtures extends Fixture
{
    public const HORAIRE_STANDARD = 'horaire-standard';
    public const HORAIRE_FLEXIBLE = 'horaire-flexible';

    public function load(ObjectManager $manager): void
    {
        $horaires = [
            [
                'label' => 'Horaire Standard',
                'tolerance' => '00:15:00',
                'plages' => [
                    ['debut' => '08:00:00', 'fin' => '12:00:00', 'ordre' => 1],
                    ['debut' => '13:00:00', 'fin' => '17:00:00', 'ordre' => 2],
                ],
                'ref' => self::HORAIRE_STANDARD,
            ],
            [
                'label' => 'Horaire Flexible',
                'tolerance' => '00:30:00',
                'plages' => [
                    ['debut' => '09:00:00', 'fin' => '13:00:00', 'ordre' => 1],
                    ['debut' => '14:00:00', 'fin' => '18:00:00', 'ordre' => 2],
                ],
                'ref' => self::HORAIRE_FLEXIBLE,
            ],
        ];

        foreach ($horaires as $data) {
            $horaire = new HoraireTravail();
            $horaire->setLabel($data['label']);
            $horaire->setToleranceRetard(\DateTimeImmutable::createFromFormat('H:i:s', $data['tolerance']));

            foreach ($data['plages'] as $plageData) {
                $plage = new PlageHoraire();
                $plage->setHeureDebut(\DateTimeImmutable::createFromFormat('H:i:s', $plageData['debut']));
                $plage->setHeureFin(\DateTimeImmutable::createFromFormat('H:i:s', $plageData['fin']));
                $plage->setOrdre($plageData['ordre']);
                $plage->setHoraireTravail($horaire);
                $horaire->addPlageHoraire($plage);
                $manager->persist($plage);
            }

            $manager->persist($horaire);
            $this->addReference($data['ref'], $horaire);
        }

        $manager->flush();
    }
}
