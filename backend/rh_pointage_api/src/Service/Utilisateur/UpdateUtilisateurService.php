<?php

namespace App\Service\Utilisateur;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Repository\DepartementRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUtilisateurService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private DepartementRepository $departementRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function update(int $id, array $data): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->find($id);

        if (!$utilisateur) {
            throw new \DomainException('Utilisateur not found');
        }

        if (isset($data['matricule'])) {
            if ($data['matricule'] === null || $data['matricule'] === '') {
                throw new \InvalidArgumentException("Field 'matricule' cannot be empty");
            }
            $utilisateur->setMatricule($data['matricule']);
        }

        if (isset($data['nom'])) {
            if ($data['nom'] === null || $data['nom'] === '') {
                throw new \InvalidArgumentException("Field 'nom' cannot be empty");
            }
            $utilisateur->setNom($data['nom']);
        }

        if (isset($data['prenom'])) {
            if ($data['prenom'] === null || $data['prenom'] === '') {
                throw new \InvalidArgumentException("Field 'prenom' cannot be empty");
            }
            $utilisateur->setPrenom($data['prenom']);
        }

        if (isset($data['email'])) {
            if ($data['email'] === null || $data['email'] === '') {
                throw new \InvalidArgumentException("Field 'email' cannot be empty");
            }
            $utilisateur->setEmail($data['email']);
        }

        if (isset($data['telephone'])) {
            if ($data['telephone'] === null || $data['telephone'] === '') {
                throw new \InvalidArgumentException("Field 'telephone' cannot be empty");
            }
            $utilisateur->setTelephone($data['telephone']);
        }

        if (isset($data['poste'])) {
            if ($data['poste'] === null || $data['poste'] === '') {
                throw new \InvalidArgumentException("Field 'poste' cannot be empty");
            }
            $utilisateur->setPoste($data['poste']);
        }

        if (array_key_exists('actif', $data)) {
            $utilisateur->setActif((bool) $data['actif']);
        }

        if (!empty($data['dateEmbauche'])) {
            try {
                $utilisateur->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
            } catch (\Exception) {
                throw new \InvalidArgumentException('Invalid dateEmbauche format');
            }
        }

        if (array_key_exists('departementId', $data)) {
            if ($data['departementId'] === null || $data['departementId'] === '') {
                throw new \InvalidArgumentException("Field 'departementId' cannot be null");
            }

            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                throw new \DomainException('Departement not found');
            }

            $utilisateur->setDepartement($departement);
        }

        if (array_key_exists('role', $data)) {
            if ($data['role'] === null || $data['role'] === '') {
                throw new \InvalidArgumentException("Field 'role' cannot be empty");
            }

            $role = RoleUtilisateur::tryFrom($data['role']);
            if (!$role) {
                throw new \DomainException('Invalid role. Allowed values: admin, manager');
            }

            $utilisateur->setRole($role);
        }

        if (!empty($data['motDePasse'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
            $utilisateur->setMotDePasse($hashedPassword);
        }

        $this->entityManager->flush();

        return $utilisateur;
    }
}