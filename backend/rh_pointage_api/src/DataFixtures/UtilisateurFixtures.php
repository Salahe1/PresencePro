<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public const USER_ADMIN = 'user-admin';
    public const USER_MANAGER = 'user-manager';

    public function load(ObjectManager $manager): void
    {
        $utilisateurs = [
            [
                'matricule' => 'ADM002',
                'nom' => 'Admin',
                'prenom' => 'Super',
                'email' => 'admin2@company.com',
                'telephone' => '0111111111',
                'dateEmbauche' => '2020-01-01',
                'poste' => 'Administrateur',
                'actif' => true,
                'departement' => DepartementFixtures::DEP_IT,
                'motDePasse' => 'admin123',
                'role' => RoleUtilisateur::ADMIN,
                'ref' => self::USER_ADMIN,
            ],
            [
                'matricule' => 'MGR002',
                'nom' => 'Manager',
                'prenom' => 'HR',
                'email' => 'manager2@company.com',
                'telephone' => '0222222222',
                'dateEmbauche' => '2021-06-01',
                'poste' => 'Manager RH',
                'actif' => true,
                'departement' => DepartementFixtures::DEP_HR,
                'motDePasse' => 'manager123',
                'role' => RoleUtilisateur::MANAGER,
                'ref' => self::USER_MANAGER,
            ],
        ];

        foreach ($utilisateurs as $data) {
            $utilisateur = new Utilisateur();
            $utilisateur->setMatricule($data['matricule']);
            $utilisateur->setNom($data['nom']);
            $utilisateur->setPrenom($data['prenom']);
            $utilisateur->setEmail($data['email']);
            $utilisateur->setTelephone($data['telephone']);
            $utilisateur->setDateEmbauche(\DateTimeImmutable::createFromFormat('Y-m-d', $data['dateEmbauche']));
            $utilisateur->setPoste($data['poste']);
            $utilisateur->setActif($data['actif']);
            $utilisateur->setDepartement($this->getReference($data['departement'], Departement::class));

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
            $utilisateur->setMotDePasse($hashedPassword);

            $utilisateur->setRole($data['role']);

            $manager->persist($utilisateur);
            $this->addReference($data['ref'], $utilisateur);
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