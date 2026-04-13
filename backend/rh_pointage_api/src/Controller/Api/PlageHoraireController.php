<?php

namespace App\Controller\Api;

use App\Entity\PlageHoraire;
use App\Repository\PlageHoraireRepository;
use App\Repository\HoraireTravailRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Plages horaires')]
#[Route('/api/plages-horaires')]
class PlageHoraireController extends AbstractController
{
    public function __construct(
        private PlageHoraireRepository $plageHoraireRepository,
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $entityManager
    ) {}

    private function normalize(PlageHoraire $plage): array
    {
        return [
            'id'            => $plage->getId(),
            'heureDebut'    => $plage->getHeureDebut()->format('H:i'),
            'heureFin'      => $plage->getHeureFin()->format('H:i'),
            'ordre'         => $plage->getOrdre(),
            'horaireTravail' => [
                'id'    => $plage->getHoraireTravail()->getId(),
                'label' => $plage->getHoraireTravail()->getLabel(),
            ],
        ];
    }

    // ─── GET /api/plages-horaires ──────────────────────────────────────────

    #[OA\Get(
        path: '/api/plages-horaires',
        summary: 'Liste toutes les plages horaires',
        description: 'Chaque plage est liée à un horaire de travail. Utiliser GET /horaires-travail/{id} pour filtrer par horaire.',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des plages',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/PlageHoraire'))
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plagesHoraires = $this->plageHoraireRepository->findAll();
        return new JsonResponse(array_map(fn(PlageHoraire $p) => $this->normalize($p), $plagesHoraires));
    }

    // ─── GET /api/plages-horaires/{id} ────────────────────────────────────

    #[OA\Get(
        path: '/api/plages-horaires/{id}',
        summary: 'Détail d\'une plage horaire',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Plage trouvée',    content: new OA\JsonContent(ref: '#/components/schemas/PlageHoraire')),
            new OA\Response(response: 404, description: 'Plage non trouvée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        if (!$plageHoraire) {
            return new JsonResponse(['error' => 'Plage horaire non trouvée'], 404);
        }
        return new JsonResponse($this->normalize($plageHoraire));
    }

    // ─── POST /api/plages-horaires ─────────────────────────────────────────

    #[OA\Post(
        path: '/api/plages-horaires',
        summary: 'Créer une plage horaire',
        description: 'L\'ordre détermine le tri des plages dans l\'affichage (1 = première plage de la journée).',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['heureDebut', 'heureFin', 'ordre', 'horaireTravailId'],
                properties: [
                    new OA\Property(property: 'heureDebut',       type: 'string',  example: '08:00'),
                    new OA\Property(property: 'heureFin',         type: 'string',  example: '12:00'),
                    new OA\Property(property: 'ordre',            type: 'integer', example: 1,
                        description: 'Position dans la journée (1 = matin, 2 = après-midi…)'),
                    new OA\Property(property: 'horaireTravailId', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Plage créée',         content: new OA\JsonContent(ref: '#/components/schemas/PlageHoraire')),
            new OA\Response(response: 400, description: 'Champs requis manquants'),
            new OA\Response(response: 404, description: 'Horaire de travail non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['heureDebut'], $data['heureFin'], $data['ordre'], $data['horaireTravailId'])) {
            return new JsonResponse(['error' => 'heureDebut, heureFin, ordre et horaireTravailId sont requises'], 400);
        }

        $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire travail non trouvé'], 404);
        }

        $plageHoraire = new PlageHoraire();
        $plageHoraire->setHeureDebut(new \DateTimeImmutable($data['heureDebut']));
        $plageHoraire->setHeureFin(new \DateTimeImmutable($data['heureFin']));
        $plageHoraire->setOrdre($data['ordre']);
        $plageHoraire->setHoraireTravail($horaireTravail);

        $this->entityManager->persist($plageHoraire);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($plageHoraire), 201);
    }

    // ─── PUT /api/plages-horaires/{id} ────────────────────────────────────

    #[OA\Put(
        path: '/api/plages-horaires/{id}',
        summary: 'Modifier une plage horaire',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'heureDebut',       type: 'string'),
                    new OA\Property(property: 'heureFin',         type: 'string'),
                    new OA\Property(property: 'ordre',            type: 'integer'),
                    new OA\Property(property: 'horaireTravailId', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Plage mise à jour', content: new OA\JsonContent(ref: '#/components/schemas/PlageHoraire')),
            new OA\Response(response: 404, description: 'Plage ou horaire non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        if (!$plageHoraire) {
            return new JsonResponse(['error' => 'Plage horaire non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['heureDebut'])) $plageHoraire->setHeureDebut(new \DateTimeImmutable($data['heureDebut']));
        if (isset($data['heureFin']))   $plageHoraire->setHeureFin(new \DateTimeImmutable($data['heureFin']));
        if (isset($data['ordre']))      $plageHoraire->setOrdre($data['ordre']);

        if (isset($data['horaireTravailId'])) {
            $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
            if (!$horaireTravail) {
                return new JsonResponse(['error' => 'Horaire travail non trouvé'], 404);
            }
            $plageHoraire->setHoraireTravail($horaireTravail);
        }

        $this->entityManager->flush();
        return new JsonResponse($this->normalize($plageHoraire));
    }

    // ─── DELETE /api/plages-horaires/{id} ─────────────────────────────────

    #[OA\Delete(
        path: '/api/plages-horaires/{id}',
        summary: 'Supprimer une plage horaire',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimée avec succès'),
            new OA\Response(response: 404, description: 'Plage non trouvée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        if (!$plageHoraire) {
            return new JsonResponse(['error' => 'Plage horaire non trouvée'], 404);
        }

        $this->entityManager->remove($plageHoraire);
        $this->entityManager->flush();
        return new JsonResponse(null, 204);
    }
}