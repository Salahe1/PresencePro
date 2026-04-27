<?php

namespace App\Service\Utilisateur;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Exception\ApiException;
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
            throw new ApiException('Utilisateur non trouvé.', 404, 'UTILISATEUR_NOT_FOUND');
        }

        if (array_key_exists('matricule', $data)) {
            $this->assertNotBlank($data['matricule'], 'matricule');
            $utilisateur->setMatricule($data['matricule']);
        }

        if (array_key_exists('nom', $data)) {
            $this->assertNotBlank($data['nom'], 'nom');
            $utilisateur->setNom($data['nom']);
        }

        if (array_key_exists('prenom', $data)) {
            $this->assertNotBlank($data['prenom'], 'prenom');
            $utilisateur->setPrenom($data['prenom']);
        }

        if (array_key_exists('email', $data)) {
            $this->assertNotBlank($data['email'], 'email');
            $utilisateur->setEmail($data['email']);
        }

        if (array_key_exists('telephone', $data)) {
            $this->assertNotBlank($data['telephone'], 'telephone');
            $utilisateur->setTelephone($data['telephone']);
        }

        if (array_key_exists('poste', $data)) {
            $this->assertNotBlank($data['poste'], 'poste');
            $utilisateur->setPoste($data['poste']);
        }

        if (array_key_exists('actif', $data)) {
            $utilisateur->setActif((bool) $data['actif']);
        }

        if (array_key_exists('dateEmbauche', $data) && $data['dateEmbauche'] !== null && $data['dateEmbauche'] !== '') {
            try {
                $utilisateur->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
            } catch (\Throwable) {
                throw new ApiException(
                    'Format de date invalide.',
                    422,
                    'INVALID_DATE_FORMAT',
                    ['dateEmbauche' => ['Format attendu : Y-m-d ou date ISO valide.']]
                );
            }
        }

        if (array_key_exists('departementId', $data)) {
            if ($data['departementId'] === null || $data['departementId'] === '') {
                throw new ApiException(
                    'Le département est obligatoire.',
                    422,
                    'VALIDATION_ERROR',
                    ['departementId' => ['Ce champ ne peut pas être vide.']]
                );
            }

            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                throw new ApiException(
                    'Département introuvable.',
                    404,
                    'DEPARTEMENT_NOT_FOUND',
                    ['departementId' => ['Aucun département trouvé pour cette valeur.']]
                );
            }

            $utilisateur->setDepartement($departement);
        }

        if (array_key_exists('role', $data)) {
            $this->assertNotBlank($data['role'], 'role');

            $role = RoleUtilisateur::tryFrom((string) $data['role']);
            if (!$role) {
                throw new ApiException(
                    'Rôle invalide. Valeurs autorisées : admin, manager.',
                    422,
                    'INVALID_ROLE',
                    ['role' => ['Valeurs autorisées : admin, manager.']]
                );
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

    private function assertNotBlank(mixed $value, string $field): void
    {
        if ($value === null || $value === '') {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                [$field => ['Ce champ ne peut pas être vide.']]
            );
        }
    }
}
