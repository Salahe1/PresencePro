<?php

namespace App\Controller\Api;

use App\Entity\HoraireTravail;
use App\Repository\HoraireTravailRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Horaires de travail')]
#[Route('/api/horaires-travail')]
class HoraireTravailController extends AbstractController
{
    public function __construct(
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $managerInterface
    ) {}

    // ─── Normalizer interne ────────────────────────────────────────────────

    private function normalize(HoraireTravail $h): array
    {
        return [
            'id'              => $h->getId(),
            'label'           => $h->getLabel(),
            'toleranceRetard' => $h->getToleranceRetard()?->format('H:i'),
            'plagesHoraires'  => array_map(fn($plage) => [
                'id'         => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin'   => $plage->getHeureFin()->format('H:i'),
                'ordre'      => $plage->getOrdre(),
            ], $h->getPlagesHoraires()->toArray()),
        ];
    }

    // ─── GET /api/horaires-travail ─────────────────────────────────────────

    #[OA\Get(
        path: '/api/horaires-travail',
        summary: 'Liste tous les horaires de travail',
        description: 'Chaque horaire inclut ses plages horaires associées.',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des horaires',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/HoraireTravail')
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $horairesTravail = $this->horaireTravailRepository->findAll();
        return new JsonResponse(array_map(fn(HoraireTravail $h) => $this->normalize($h), $horairesTravail));
    }

    // ─── GET /api/horaires-travail/{id} ───────────────────────────────────

    #[OA\Get(
        path: '/api/horaires-travail/{id}',
        summary: 'Détail d\'un horaire de travail',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Horaire trouvé',    content: new OA\JsonContent(ref: '#/components/schemas/HoraireTravail')),
            new OA\Response(response: 404, description: 'Horaire non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }
        return new JsonResponse($this->normalize($horaireTravail));
    }

    // ─── POST /api/horaires-travail ────────────────────────────────────────

    #[OA\Post(
        path: '/api/horaires-travail',
        summary: 'Créer un horaire de travail',
        description: 'La tolérance est une heure au format HH:MM (ex: 00:10 = 10 minutes de grâce).',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'toleranceRetard'],
                properties: [
                    new OA\Property(property: 'label',           type: 'string', example: 'Horaire standard 8h-17h'),
                    new OA\Property(property: 'toleranceRetard', type: 'string', example: '00:10',
                        description: 'Tolérance de retard au format HH:MM'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Horaire créé',          content: new OA\JsonContent(ref: '#/components/schemas/HoraireTravail')),
            new OA\Response(response: 400, description: 'Champs requis manquants'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['label']) || !isset($data['toleranceRetard'])) {
            return new JsonResponse(['error' => 'Label et toleranceRetard sont requises'], 400);
        }

        $horaireTravail = new HoraireTravail();
        $horaireTravail->setLabel($data['label']);
        $horaireTravail->setToleranceRetard(new \DateTimeImmutable($data['toleranceRetard']));

        $this->managerInterface->persist($horaireTravail);
        $this->managerInterface->flush();

        return new JsonResponse($this->normalize($horaireTravail), 201);
    }

    // ─── PUT /api/horaires-travail/{id} ───────────────────────────────────

    #[OA\Put(
        path: '/api/horaires-travail/{id}',
        summary: 'Modifier un horaire de travail',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label',           type: 'string'),
                    new OA\Property(property: 'toleranceRetard', type: 'string', example: '00:05'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Horaire mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/HoraireTravail')),
            new OA\Response(response: 404, description: 'Horaire non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['label'])) {
            $horaireTravail->setLabel($data['label']);
        }
        if (isset($data['toleranceRetard'])) {
            $horaireTravail->setToleranceRetard(new \DateTimeImmutable($data['toleranceRetard']));
        }

        $this->managerInterface->flush();
        return new JsonResponse($this->normalize($horaireTravail));
    }

    // ─── DELETE /api/horaires-travail/{id} ────────────────────────────────

    #[OA\Delete(
        path: '/api/horaires-travail/{id}',
        summary: 'Supprimer un horaire de travail',
        description: 'Attention : les employés liés perdront leur référence horaire.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimé avec succès'),
            new OA\Response(response: 404, description: 'Horaire non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }

        $this->managerInterface->remove($horaireTravail);
        $this->managerInterface->flush();
        return new JsonResponse(null, 204);
    }
}