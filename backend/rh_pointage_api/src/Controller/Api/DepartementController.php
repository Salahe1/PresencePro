<?php

namespace App\Controller\Api;

use App\Entity\Departement;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Départements')]
#[Route('/api/departements')]
class DepartementController extends AbstractController
{
    public function __construct(
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ) {}

    // ─── GET /api/departements ─────────────────────────────────────────────

    #[OA\Get(
        path: '/api/departements',
        summary: 'Liste tous les départements',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des départements',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Departement')
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $departements = $this->departementRepository->findAll();
        $data = array_map(fn(Departement $d) => [
            'id'    => $d->getId(),
            'label' => $d->getLabel(),
        ], $departements);

        return new JsonResponse($data);
    }

    // ─── GET /api/departements/{id} ────────────────────────────────────────

    #[OA\Get(
        path: '/api/departements/{id}',
        summary: 'Détail d\'un département',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Département trouvé',    content: new OA\JsonContent(ref: '#/components/schemas/Departement')),
            new OA\Response(response: 404, description: 'Département non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        if (!$departement) {
            return new JsonResponse(['error' => 'Departement non trouvé'], 404);
        }

        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()]);
    }

    // ─── POST /api/departements ────────────────────────────────────────────

    #[OA\Post(
        path: '/api/departements',
        summary: 'Créer un département',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', example: 'Ressources Humaines'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Département créé',          content: new OA\JsonContent(ref: '#/components/schemas/Departement')),
            new OA\Response(response: 400, description: 'Label requis',               content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['label'])) {
            return new JsonResponse(['error' => 'Label is required'], 400);
        }

        $departement = new Departement();
        $departement->setLabel($data['label']);

        $this->entityManager->persist($departement);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()], 201);
    }

    // ─── PUT /api/departements/{id} ────────────────────────────────────────

    #[OA\Put(
        path: '/api/departements/{id}',
        summary: 'Modifier un département',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', example: 'Direction Générale'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Département mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/Departement')),
            new OA\Response(response: 404, description: 'Département non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        if (!$departement) {
            return new JsonResponse(['error' => 'Departement not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['label'])) {
            $departement->setLabel($data['label']);
        }

        $this->entityManager->flush();
        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()]);
    }

    // ─── DELETE /api/departements/{id} ────────────────────────────────────

    #[OA\Delete(
        path: '/api/departements/{id}',
        summary: 'Supprimer un département',
        description: 'Attention : vérifier qu\'aucun employé n\'est rattaché avant de supprimer.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimé avec succès'),
            new OA\Response(response: 404, description: 'Département non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        if (!$departement) {
            return new JsonResponse(['error' => 'Departement not found'], 404);
        }

        $this->entityManager->remove($departement);
        $this->entityManager->flush();

        return new JsonResponse(null, 204); // ← corrigé : null au lieu d'un body sur 204
    }
}