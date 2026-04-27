<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\HoraireTravail;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DepartementFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEP_IT = 'dep-it';
    public const DEP_HR = 'dep-hr';
    public const DEP_FINANCE = 'dep-finance';

    public function load(ObjectManager $manager): void
    {
        $departements = [
            [
                'label' => 'Informatique',
                'horaire' => HoraireTravailFixtures::HORAIRE_STANDARD,
                'ref' => self::DEP_IT,
            ],
            [
                'label' => 'Ressources Humaines',
                'horaire' => HoraireTravailFixtures::HORAIRE_FLEXIBLE,
                'ref' => self::DEP_HR,
            ],
            [
                'label' => 'Finance',
                'horaire' => HoraireTravailFixtures::HORAIRE_STANDARD,
                'ref' => self::DEP_FINANCE,
            ],
        ];

        foreach ($departements as $data) {
            $departement = new Departement();
            $departement->setLabel($data['label']);
            $departement->setHoraireTravail($this->getReference($data['horaire'], HoraireTravail::class));
            $manager->persist($departement);
            $this->addReference($data['ref'], $departement);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            HoraireTravailFixtures::class,
        ];
    }
}
