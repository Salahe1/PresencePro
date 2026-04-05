<?php

namespace App\DataFixtures;

use App\Entity\CalendrierTravail;
use App\Entity\Departement;
use App\Enum\TypeJour;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CalendrierTravailFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $calendriers = [
            [
                'date' => '2024-04-01',
                'typeJour' => TypeJour::OUVRABLE,
                'estTravaille' => true,
                'description' => 'Jour ouvrable',
                'departement' => DepartementFixtures::DEP_IT,
            ],
            [
                'date' => '2024-04-06',
                'typeJour' => TypeJour::WEEKEND,
                'estTravaille' => false,
                'description' => 'Samedi',
                'departement' => DepartementFixtures::DEP_IT,
            ],
            [
                'date' => '2024-04-07',
                'typeJour' => TypeJour::WEEKEND,
                'estTravaille' => false,
                'description' => 'Dimanche',
                'departement' => DepartementFixtures::DEP_IT,
            ],
            [
                'date' => '2024-05-01',
                'typeJour' => TypeJour::FERIE,
                'estTravaille' => false,
                'description' => 'Fête du Travail',
                'departement' => DepartementFixtures::DEP_HR,
            ],
            [
                'date' => '2024-04-01',
                'typeJour' => TypeJour::OUVRABLE,
                'estTravaille' => true,
                'description' => 'Jour ouvrable',
                'departement' => DepartementFixtures::DEP_HR,
            ],
        ];

        foreach ($calendriers as $data) {
            $calendrier = new CalendrierTravail();
            $calendrier->setDateJour(\DateTimeImmutable::createFromFormat('Y-m-d', $data['date']));
            $calendrier->setTypeJour($data['typeJour']);
            $calendrier->setEstTravaille($data['estTravaille']);
            $calendrier->setDescription($data['description']);
            $calendrier->setDepartement($this->getReference($data['departement'], Departement::class));
            $manager->persist($calendrier);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DepartementFixtures::class,
        ];
    }
}