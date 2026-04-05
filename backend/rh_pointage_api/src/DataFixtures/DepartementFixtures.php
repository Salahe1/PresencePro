<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DepartementFixtures extends Fixture
{
    public const DEP_IT = 'dep-it';
    public const DEP_HR = 'dep-hr';
    public const DEP_FINANCE = 'dep-finance';

    public function load(ObjectManager $manager): void
    {
        $departements = [
            ['label' => 'Informatique', 'ref' => self::DEP_IT],
            ['label' => 'Ressources Humaines', 'ref' => self::DEP_HR],
            ['label' => 'Finance', 'ref' => self::DEP_FINANCE],
        ];

        foreach ($departements as $data) {
            $departement = new Departement();
            $departement->setLabel($data['label']);
            $manager->persist($departement);
            $this->addReference($data['ref'], $departement);
        }

        $manager->flush();
    }
}