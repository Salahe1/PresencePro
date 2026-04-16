<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Enum\RoleUtilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\DepartementRepository;
use App\Service\Utilisateur\CreateUtilisateurService;
use App\Service\Utilisateur\UpdateUtilisateurService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Utilisateurs (admin)')]
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private CreateUtilisateurService $createUtilisateurService,
        private UpdateUtilisateurService $updateUtilisateurService
    ) {}

    private function serializeUtilisateur(Utilisateur $utilisateur): array
    {
        return [
            'id'            => $utilisateur->getId(),
            'matricule'     => $utilisateur->getMatricule(),
            'nom'           => $utilisateur->getNom(),
            'prenom'        => $utilisateur->getPrenom(),
            'email'         => $utilisateur->getEmail(),
            'telephone'     => $utilisateur->getTelephone(),
            'poste'         => $utilisateur->getPoste(),
            'dateEmbauche'  => $utilisateur->getDateEmbauche()?->format('Y-m-d'),
            'actif'         => $utilisateur->isActif(),
            'role'          => $utilisateur->getRole()?->value,
            'departement' => $utilisateur->getDepartement() ? [
                                'id' => $utilisateur->getDepartement()->getId(),
                                'label' => $utilisateur->getDepartement()->getLabel(),] : null,
        ];
    }

    // ─── GET /api/utilisateurs ─────────────────────────────────────────────

    #[OA\Get(
        path: '/api/utilisateurs',
        summary: 'Liste tous les comptes administrateurs',
        description: 'Accessible uniquement par un super-admin. Les mots de passe ne sont jamais exposés.',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Utilisateur'))
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $utilisateurs = $this->utilisateurRepository->findAll();
        return new JsonResponse(array_map(fn(Utilisateur $u) => $this->serializeUtilisateur($u), $utilisateurs));
    }

    // ─── GET /api/utilisateurs/{id} ───────────────────────────────────────

    #[OA\Get(
        path: '/api/utilisateurs/{id}',
        summary: 'Détail d\'un compte utilisateur',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur trouvé',    content: new OA\JsonContent(ref: '#/components/schemas/Utilisateur')),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur not found'], 404);
        }
        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    // ─── POST /api/utilisateurs ────────────────────────────────────────────

    #[OA\Post(
        path: '/api/utilisateurs',
        summary: 'Créer un compte utilisateur (admin/manager)',
        description: 'Réservé au super-admin. Le mot de passe est haché automatiquement. Rôles disponibles : admin, manager.',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['matricule', 'nom', 'prenom', 'email', 'telephone', 'poste', 'motDePasse', 'role'],
                properties: [
                    new OA\Property(property: 'matricule',   type: 'string',  example: 'ADM-001'),
                    new OA\Property(property: 'nom',         type: 'string',  example: 'Admin'),
                    new OA\Property(property: 'prenom',      type: 'string',  example: 'Super'),
                    new OA\Property(property: 'email',       type: 'string',  format: 'email', example: 'admin@grh.ma'),
                    new OA\Property(property: 'telephone',   type: 'string',  example: '+212600000000'),
                    new OA\Property(property: 'departementId', type: 'integer', example: 1),
                    new OA\Property(property: 'poste',       type: 'string',  example: 'Responsable RH'),
                    new OA\Property(property: 'motDePasse',  type: 'string',  format: 'password', example: 'Str0ngP@ss!'),
                    new OA\Property(property: 'role',        type: 'string',  example: 'admin',
                        description: 'Valeurs acceptées : admin | manager'),
                    new OA\Property(property: 'dateEmbauche',type: 'string',  format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'actif',       type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé',         content: new OA\JsonContent(ref: '#/components/schemas/Utilisateur')),
            new OA\Response(response: 400, description: 'Champ manquant ou rôle invalide'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        try {
            $utilisateur = $this->createUtilisateurService->create($data);
        } catch (\InvalidArgumentException | \DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Unexpected server error'], 500);
        }

        return new JsonResponse($this->serializeUtilisateur($utilisateur), 201);
    }

    // ─── PUT /api/utilisateurs/{id} ───────────────────────────────────────

    #[OA\Put(
        path: '/api/utilisateurs/{id}',
        summary: 'Modifier un compte utilisateur',
        description: 'Le motDePasse n\'est mis à jour que s\'il est fourni. Ne jamais envoyer le hash actuel.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom',        type: 'string'),
                    new OA\Property(property: 'prenom',     type: 'string'),
                    new OA\Property(property: 'email',      type: 'string', format: 'email'),
                    new OA\Property(property: 'telephone',  type: 'string'),
                    new OA\Property(property: 'poste',      type: 'string'),
                    new OA\Property(property: 'motDePasse', type: 'string', format: 'password',
                        description: 'Optionnel — laisser vide pour ne pas changer'),
                    new OA\Property(property: 'role',       type: 'string'),
                    new OA\Property(property: 'actif',      type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/Utilisateur')),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        try {
         $utilisateur = $this->updateUtilisateurService->update($id, $data);
     } catch (\DomainException $e) {
            $status = match ($e->getMessage()) {
                'Utilisateur not found' => 404,
                'Departement not found' => 400,
                'Invalid role. Allowed values: admin, manager' => 400,
                default => 400,
            };

            return new JsonResponse(['error' => $e->getMessage()], $status);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception) {
            return new JsonResponse(['error' => 'Unexpected server error'], 500);
        }

        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    // ─── DELETE /api/utilisateurs/{id} ────────────────────────────────────

    #[OA\Delete(
        path: '/api/utilisateurs/{id}',
        summary: 'Supprimer un compte utilisateur',
        description: 'Action irréversible. Réservée au super-admin.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimé avec succès'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur not found'], 404);
        }

        $this->entityManager->remove($utilisateur);
        $this->entityManager->flush();
        return new JsonResponse(null, 204);
    }
}