<?php

namespace App\Service\Utilisateur;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Exception\ApiException;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Common\DateParserService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUtilisateurService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private DepartementRepository $departementRepository,
        private DateParserService $dateParserService
    ) {
    }

    public function create(array $data): Utilisateur
    {
        $requiredFields = [
            'matricule',
            'nom', 'prenom',
            'email', 'telephone',
            'departementId', 'poste',
            'motDePasse', 'role',
        ];

        $details = [];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $details[$field] = ['Ce champ est obligatoire.'];
            }
        }

        if (!empty($details)) {
            throw new ApiException(
                'Les données envoyées sont invalides.', 422, 'VALIDATION_ERROR', $details
            );
        }

        $departement = $this->departementRepository->find($data['departementId']);
        if (!$departement) {
            throw new ApiException(
                'Département introuvable.', 404, 'DEPARTEMENT_NOT_FOUND',
                ['departementId' => ['Aucun département trouvé pour cette valeur.']]
            );
        }

        $role = RoleUtilisateur::tryFrom((string) $data['role']);
        if (!$role) {
            throw new ApiException(
                'Rôle invalide. Valeurs autorisées : admin, manager.', 422, 'INVALID_ROLE',
                ['role' => ['Valeurs autorisées : admin, manager.']]
            );
        }

        $dateEmbauche = $this->dateParserService->parseYmdOrToday( $data['dateEmbauche'] ?? null, 'dateEmbauche' );

        $utilisateur = new Utilisateur();
        $utilisateur->setMatricule($data['matricule']);
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setEmail($data['email']);
        $utilisateur->setTelephone($data['telephone']);
        $utilisateur->setPoste($data['poste']);
        $utilisateur->setDepartement($departement);
        $utilisateur->setRole($role);
        $utilisateur->setDateEmbauche($dateEmbauche);

        if (array_key_exists('actif', $data)) {
            $utilisateur->setActif((bool) $data['actif']);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
        $utilisateur->setMotDePasse($hashedPassword);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }
}
