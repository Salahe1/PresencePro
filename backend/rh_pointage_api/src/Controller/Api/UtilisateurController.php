<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $utilisateurs = $this->utilisateurRepository->findAll();
        $data = array_map(fn(Utilisateur $utilisateur) => $this->serializeUtilisateur($utilisateur), $utilisateurs);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur not found'], 404);
        }

        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $requiredFields = ['matricule', 'nom', 'prenom', 'email', 'telephone', 'poste', 'motDePasse', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $utilisateur = new Utilisateur();
        
        // Inherited Employe fields
        $utilisateur->setMatricule($data['matricule']);
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setEmail($data['email']);
        $utilisateur->setTelephone($data['telephone']);
        $utilisateur->setPoste($data['poste']);
        
        if (!empty($data['dateEmbauche'])) {
            $utilisateur->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
        } else {
            $utilisateur->setDateEmbauche(new \DateTimeImmutable());
        }
        
        if (isset($data['actif'])) {
            $utilisateur->setActif((bool)$data['actif']);
        }

        // Utilisateur specific fields
        $role = RoleUtilisateur::tryFrom($data['role']);
        if (!$role) {
            return new JsonResponse(['error' => 'Invalid role. Allowed values: admin, manager'], 400);
        }
        $utilisateur->setRole($role);
        
        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
        $utilisateur->setMotDePasse($hashedPassword);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeUtilisateur($utilisateur), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['matricule'])) $utilisateur->setMatricule($data['matricule']);
        if (isset($data['nom'])) $utilisateur->setNom($data['nom']);
        if (isset($data['prenom'])) $utilisateur->setPrenom($data['prenom']);
        if (isset($data['email'])) $utilisateur->setEmail($data['email']);
        if (isset($data['telephone'])) $utilisateur->setTelephone($data['telephone']);
        if (isset($data['poste'])) $utilisateur->setPoste($data['poste']);
        
        if (!empty($data['dateEmbauche'])) {
            $utilisateur->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
        }
        
        if (isset($data['actif'])) $utilisateur->setActif((bool)$data['actif']);

        if (!empty($data['role'])) {
            $role = RoleUtilisateur::tryFrom($data['role']);
            if ($role) {
                $utilisateur->setRole($role);
            }
        }

        if (!empty($data['motDePasse'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $data['motDePasse']);
            $utilisateur->setMotDePasse($hashedPassword);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur not found'], 404);
        }

        $this->entityManager->remove($utilisateur);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur deleted'], 204);
    }

    private function serializeUtilisateur(Utilisateur $utilisateur): array
    {
        return [
            'id' => $utilisateur->getId(),
            'matricule' => $utilisateur->getMatricule(),
            'nom' => $utilisateur->getNom(),
            'prenom' => $utilisateur->getPrenom(),
            'email' => $utilisateur->getEmail(),
            'telephone' => $utilisateur->getTelephone(),
            'poste' => $utilisateur->getPoste(),
            'dateEmbauche' => $utilisateur->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $utilisateur->isActif(),
            'role' => $utilisateur->getRole()?->value,
            'departement_id' => $utilisateur->getDepartement()?->getId(),
        ];
    }
}
