<?php

namespace App\Controller\Api;

use App\Entity\CalendrierTravail;
use App\Enum\TypeJour;
use App\Exception\ApiException;
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
    use ApiControllerHelperTrait;

    public function __construct(
        private CalendrierTravailRepository $calendrierTravailRepository,
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ) {}

    private function normalize(CalendrierTravail $c): array
    {
        return [
            'id' => $c->getId(),
            'dateJour' => $c->getDateJour()?->format('Y-m-d'),
            'typeJour' => $c->getTypeJour()?->value,
            'estTravaille' => $c->isEstTravaille(),
            'description' => $c->getDescription(),
            'departement' => $c->getDepartement() ? [
                'id' => $c->getDepartement()->getId(),
                'label' => $c->getDepartement()->getLabel(),
            ] : null,
        ];
    }

    private function resolveTypeJour(mixed $value): TypeJour
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                ['typeJour' => ['Ce champ est obligatoire.']]
            );
        }

        $normalized = strtolower(trim($value));
        $typeJour = TypeJour::tryFrom($normalized);

        if ($typeJour !== null) {
            return $typeJour;
        }

        foreach (TypeJour::cases() as $case) {
            if (strtoupper($case->name) === strtoupper(trim($value))) {
                return $case;
            }
        }

        throw new ApiException(
            'typeJour invalide. Valeurs acceptées : ouvrable, ferie, conge, weekend.',
            422,
            'INVALID_TYPE_JOUR',
            ['typeJour' => ['Valeurs acceptées : ouvrable, ferie, conge, weekend.']]
        );
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $entries = $this->calendrierTravailRepository->findAll();

        return new JsonResponse(array_map(
            fn(CalendrierTravail $c) => $this->normalize($c),
            $entries
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $entry = $this->calendrierTravailRepository->find($id);
        $this->assertFound($entry, 'Calendrier de travail non trouvé.', 'CALENDRIER_TRAVAIL_NOT_FOUND');

        return new JsonResponse($this->normalize($entry));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $this->requireFields($data, ['dateJour', 'typeJour']);

        $entry = new CalendrierTravail();
        $entry->setDateJour($this->parseDate($data['dateJour'], 'dateJour', 'Y-m-d'));
        $entry->setTypeJour($this->resolveTypeJour($data['typeJour']));
        $entry->setEstTravaille((bool) ($data['estTravaille'] ?? true));
        $entry->setDescription($data['description'] ?? null);

        if (array_key_exists('departementId', $data) && $data['departementId'] !== null && $data['departementId'] !== '') {
            $departement = $this->departementRepository->find($data['departementId']);
            $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');
            $entry->setDepartement($departement);
        }

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($entry), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $entry = $this->calendrierTravailRepository->find($id);
        $this->assertFound($entry, 'Calendrier de travail non trouvé.', 'CALENDRIER_TRAVAIL_NOT_FOUND');

        $data = $this->parseJson($request);

        if (array_key_exists('dateJour', $data)) {
            $entry->setDateJour($this->parseDate($data['dateJour'], 'dateJour', 'Y-m-d'));
        }
        if (array_key_exists('typeJour', $data)) {
            $entry->setTypeJour($this->resolveTypeJour($data['typeJour']));
        }
        if (array_key_exists('estTravaille', $data)) {
            $entry->setEstTravaille((bool) $data['estTravaille']);
        }
        if (array_key_exists('description', $data)) {
            $entry->setDescription($data['description']);
        }
        if (array_key_exists('departementId', $data)) {
            if ($data['departementId'] === null || $data['departementId'] === '') {
                $entry->setDepartement(null);
            } else {
                $departement = $this->departementRepository->find($data['departementId']);
                $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');
                $entry->setDepartement($departement);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse($this->normalize($entry));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $entry = $this->calendrierTravailRepository->find($id);
        $this->assertFound($entry, 'Calendrier de travail non trouvé.', 'CALENDRIER_TRAVAIL_NOT_FOUND');

        $this->entityManager->remove($entry);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
