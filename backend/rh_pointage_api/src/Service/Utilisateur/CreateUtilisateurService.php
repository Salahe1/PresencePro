<?php

namespace App\Service\Utilisateur;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUtilisateurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private DepartementRepository $departementRepository,
    ) {
    }

    public function create(array $data): Utilisateur
    {
        $requiredFields = [
            'matricule', 'nom',
            'prenom', 'email',
            'telephone', 'departementId',
            'poste',  'motDePasse', 'role',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                throw new \InvalidArgumentException("Field '$field' is required");
            }
        }

        $departement = $this->departementRepository->find($data['departementId']);
        if (!$departement) {
            throw new \DomainException('Departement not found');
        }

        $role = RoleUtilisateur::tryFrom($data['role']);
        if (!$role) {
            throw new \DomainException('Invalid role. Allowed values: admin, manager');
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setMatricule($data['matricule']);
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setEmail($data['email']);
        $utilisateur->setTelephone($data['telephone']);
        $utilisateur->setPoste($data['poste']);
        $utilisateur->setDepartement($departement);
        $utilisateur->setRole($role);

        $utilisateur->setDateEmbauche(
            !empty($data['dateEmbauche'])
                ? new \DateTimeImmutable($data['dateEmbauche'])
                : new \DateTimeImmutable()
        );

        if (isset($data['actif'])) {
            $utilisateur->setActif((bool) $data['actif']);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
        $utilisateur->setMotDePasse($hashedPassword);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }
}