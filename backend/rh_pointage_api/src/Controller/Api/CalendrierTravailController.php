<?php

namespace App\Controller\Api;

use App\Entity\CalendrierTravail;
use App\Enum\TypeJour;
use App\Repository\CalendrierTravailRepository;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Calendriers de travail')]
#[Route('/api/calendriers-travail')]
class CalendrierTravailController extends AbstractController
{
    public function __construct(
        private CalendrierTravailRepository $calendrierTravailRepository,
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ){}

    private function normalize(CalendrierTravail $c): array
    {
        return [
            'id'          => $c->getId(),
            'dateJour'    => $c->getDateJour()?->format('Y-m-d'),
            'typeJour'    => $c->getTypeJour()?->value,
            'estTravaille'=> $c->isEstTravaille(),
            'description' => $c->getDescription(),
            'departement' => $c->getDepartement() ? [
                'id'    => $c->getDepartement()->getId(),
                'label' => $c->getDepartement()->getLabel(),
            ] : null,
        ];
    }

    // ─── GET /api/calendriers-travail ──────────────────────────────────────

    #[OA\Get(
        path: '/api/calendriers-travail',
        summary: 'Liste toutes les entrées du calendrier',
        description: 'Retourne jours fériés, jours de congé et jours travaillés. Filtrer côté client par département ou période.',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste du calendrier',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CalendrierTravail'))
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $calendriersTravail = $this->calendrierTravailRepository->findAll();
        return new JsonResponse(array_map(fn(CalendrierTravail $c) => $this->normalize($c), $calendriersTravail));
    }

    // ─── GET /api/calendriers-travail/{id} ────────────────────────────────

    #[OA\Get(
        path: '/api/calendriers-travail/{id}',
        summary: 'Détail d\'une entrée calendrier',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Entrée trouvée',    content: new OA\JsonContent(ref: '#/components/schemas/CalendrierTravail')),
            new OA\Response(response: 404, description: 'Entrée non trouvée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $c = $this->calendrierTravailRepository->find($id);
        if (!$c) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }
        return new JsonResponse($this->normalize($c));
    }

    // ─── POST /api/calendriers-travail ─────────────────────────────────────

    #[OA\Post(
        path: '/api/calendriers-travail',
        summary: 'Créer une entrée dans le calendrier',
        description: 'Valeurs possibles pour typeJour : OUVRABLE, FERIE, CONGE, WEEKEND.',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['dateJour', 'typeJour'],
                properties: [
                    new OA\Property(property: 'dateJour',      type: 'string',  format: 'date',    example: '2025-01-01'),
                    new OA\Property(property: 'typeJour',      type: 'string',  example: 'FERIE',
                        description: 'Valeurs : OUVRABLE | FERIE | CONGE | WEEKEND'),
                    new OA\Property(property: 'estTravaille',  type: 'boolean', example: false),
                    new OA\Property(property: 'description',   type: 'string',  example: 'Jour de l\'An', nullable: true),
                    new OA\Property(property: 'departementId', type: 'integer', example: 1, nullable: true,
                        description: 'Si null, s\'applique à toute l\'entreprise'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Entrée créée',          content: new OA\JsonContent(ref: '#/components/schemas/CalendrierTravail')),
            new OA\Response(response: 400, description: 'Champs requis manquants ou typeJour invalide'),
            new OA\Response(response: 404, description: 'Département non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['dateJour']) || !isset($data['typeJour'])) {
            return new JsonResponse(['error' => 'dateJour et typeJour sont requis'], 400);
        }

        $typeJour = TypeJour::tryFrom($data['typeJour']);
        if (!$typeJour) {
            return new JsonResponse(['error' => 'typeJour invalide. Valeurs acceptées : OUVRABLE, FERIE, CONGE, WEEKEND'], 400);
        }

        $c = new CalendrierTravail();
        $c->setDateJour(new \DateTimeImmutable($data['dateJour']));
        $c->setTypeJour($typeJour);
        $c->setEstTravaille($data['estTravaille'] ?? true);
        $c->setDescription($data['description'] ?? null);

        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                return new JsonResponse(['error' => 'Departement non trouvé'], 404);
            }
            $c->setDepartement($departement);
        }

        $this->entityManager->persist($c);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($c), 201);
    }

    // ─── PUT /api/calendriers-travail/{id} ────────────────────────────────

    #[OA\Put(
        path: '/api/calendriers-travail/{id}',
        summary: 'Modifier une entrée du calendrier',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'dateJour',      type: 'string',  format: 'date'),
                    new OA\Property(property: 'typeJour',      type: 'string'),
                    new OA\Property(property: 'estTravaille',  type: 'boolean'),
                    new OA\Property(property: 'description',   type: 'string',  nullable: true),
                    new OA\Property(property: 'departementId', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Entrée mise à jour', content: new OA\JsonContent(ref: '#/components/schemas/CalendrierTravail')),
            new OA\Response(response: 404, description: 'Entrée ou département non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $c = $this->calendrierTravailRepository->find($id);
        if (!$c) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['dateJour'])) {
            $c->setDateJour(new \DateTimeImmutable($data['dateJour']));
        }
        if (isset($data['typeJour'])) {
            $typeJour = TypeJour::tryFrom($data['typeJour']);
            if (!$typeJour) {
                return new JsonResponse(['error' => 'typeJour invalide'], 400);
            }
            $c->setTypeJour($typeJour);
        }
        if (isset($data['estTravaille'])) {
            $c->setEstTravaille((bool) $data['estTravaille']);
        }
        if (array_key_exists('description', $data)) {
            $c->setDescription($data['description']);
        }
        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                return new JsonResponse(['error' => 'Departement non trouvé'], 404);
            }
            $c->setDepartement($departement);
        }

        $this->entityManager->flush();
        return new JsonResponse($this->normalize($c));
    }

    // ─── DELETE /api/calendriers-travail/{id} ─────────────────────────────

    #[OA\Delete(
        path: '/api/calendriers-travail/{id}',
        summary: 'Supprimer une entrée du calendrier',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimée avec succès'),
            new OA\Response(response: 404, description: 'Entrée non trouvée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $c = $this->calendrierTravailRepository->find($id);
        if (!$c) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }

        $this->entityManager->remove($c);
        $this->entityManager->flush();
        return new JsonResponse(null, 204);
    }
}