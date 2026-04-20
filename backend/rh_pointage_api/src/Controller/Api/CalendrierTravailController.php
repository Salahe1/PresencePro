<?php

namespace App\Controller\Api;

use App\Entity\CalendrierTravail;
use App\Entity\Departement;
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
                'Les données envoyées sont invalides.', 422,
                'VALIDATION_ERROR',['typeJour' => ['Ce champ est obligatoire.']]
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

    private function resolveDepartementFromPayload(array $data): ?Departement
    {
        if (!array_key_exists('departementId', $data) || $data['departementId'] === null || $data['departementId'] === '') {
            return null;
        }

        $departement = $this->departementRepository->find($data['departementId']);
        $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');

        return $departement;
    }

    private function assertNoDuplicateRule( \DateTimeImmutable $dateJour, ?Departement $departement, ?int $excludeId = null ): void 
    {
        $exists = $this->calendrierTravailRepository->existsForDateAndDepartment(
            $dateJour,
            $departement,
            $excludeId
        );

        if ($exists) {
            throw new ApiException(
                'Une règle de calendrier existe déjà pour cette date et ce périmètre.',
                409,
                'CALENDRIER_RULE_ALREADY_EXISTS',
                [
                    'dateJour' => ['Une règle existe déjà pour cette date.'],
                    'departementId' => [
                        $departement === null
                            ? 'Une règle globale existe déjà pour cette date.'
                            : 'Une règle existe déjà pour ce département à cette date.'
                    ],
                ]
            );
        }
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $entries = $this->calendrierTravailRepository->findAll();

        return new JsonResponse(array_map(
            fn (CalendrierTravail $c) => $this->normalize($c),
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
        $this->requireFields($data, ['dateJour', 'typeJour', 'estTravaille']);

        $dateJour = $this->parseDate($data['dateJour'], 'dateJour', 'Y-m-d');
        $typeJour = $this->resolveTypeJour($data['typeJour']);
        $departement = $this->resolveDepartementFromPayload($data);

        $this->assertNoDuplicateRule($dateJour, $departement);

        $entry = new CalendrierTravail();
        $entry->setDateJour($dateJour);
        $entry->setTypeJour($typeJour);
        $entry->setEstTravaille((bool) $data['estTravaille']);
        $entry->setDescription($data['description'] ?? null);
        $entry->setDepartement($departement);

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

        $finalDateJour = $entry->getDateJour();
        $finalTypeJour = $entry->getTypeJour();
        $finalEstTravaille = $entry->isEstTravaille();
        $finalDescription = $entry->getDescription();
        $finalDepartement = $entry->getDepartement();

        if (array_key_exists('dateJour', $data)) {
            $finalDateJour = $this->parseDate($data['dateJour'], 'dateJour', 'Y-m-d');
        }

        if (array_key_exists('typeJour', $data)) {
            $finalTypeJour = $this->resolveTypeJour($data['typeJour']);
        }

        if (array_key_exists('estTravaille', $data)) {
            $finalEstTravaille = (bool) $data['estTravaille'];
        }

        if (array_key_exists('description', $data)) {
            $finalDescription = $data['description'];
        }

        if (array_key_exists('departementId', $data)) {
            if ($data['departementId'] === null || $data['departementId'] === '') {
                $finalDepartement = null;
            } else {
                $departement = $this->departementRepository->find($data['departementId']);
                $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');
                $finalDepartement = $departement;
            }
        }

        if ($finalDateJour === null) {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                ['dateJour' => ['Ce champ est obligatoire.']]
            );
        }

        if ($finalTypeJour === null) {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                ['typeJour' => ['Ce champ est obligatoire.']]
            );
        }

        $this->assertNoDuplicateRule($finalDateJour, $finalDepartement, $entry->getId());

        $entry->setDateJour($finalDateJour);
        $entry->setTypeJour($finalTypeJour);
        $entry->setEstTravaille($finalEstTravaille);
        $entry->setDescription($finalDescription);
        $entry->setDepartement($finalDepartement);

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