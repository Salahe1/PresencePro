<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Employe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EmployeFixtures extends Fixture implements DependentFixtureInterface
{
    public const EMP_JOHN = 'emp-john';
    public const EMP_JANE = 'emp-jane';

    public function load(ObjectManager $manager): void
    {
        $employes = [
            [
                'matricule' => 'EMP003',
                'nom' => 'Doe',
                'prenom' => 'John',
                'email' => 'john.doe2@company.com',
                'telephone' => '0123456789',
                'dateEmbauche' => '2023-01-15',
                'poste' => 'Développeur',
                'actif' => true,
                'departement' => DepartementFixtures::DEP_IT,
                'ref' => self::EMP_JOHN,
            ],
            [
                'matricule' => 'EMP004',
                'nom' => 'Smith',
                'prenom' => 'Jane',
                'email' => 'jane.smith2@company.com',
                'telephone' => '0987654321',
                'dateEmbauche' => '2023-03-20',
                'poste' => 'Manager RH',
                'actif' => true,
                'departement' => DepartementFixtures::DEP_HR,
                'ref' => self::EMP_JANE,
            ],
        ];

        foreach ($employes as $data) {
            $employe = new Employe();
            $employe->setMatricule($data['matricule']);
            $employe->setNom($data['nom']);
            $employe->setPrenom($data['prenom']);
            $employe->setEmail($data['email']);
            $employe->setTelephone($data['telephone']);
            $employe->setDateEmbauche(\DateTimeImmutable::createFromFormat('Y-m-d', $data['dateEmbauche']));
            $employe->setPoste($data['poste']);
            $employe->setActif($data['actif']);
            $employe->setDepartement($this->getReference($data['departement'], Departement::class));
            $manager->persist($employe);
            $this->addReference($data['ref'], $employe);
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