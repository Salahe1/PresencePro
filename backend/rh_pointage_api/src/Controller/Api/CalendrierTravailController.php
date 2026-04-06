<?php

namespace App\Controller\Api;

use App\Entity\CalendrierTravail;
use App\Enum\TypeJour;
use App\Repository\CalendrierTravailRepository;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/calendriers-travail')]
class CalendrierTravailController extends AbstractController
{
    public function __construct(
        private CalendrierTravailRepository $calendrierTravailRepository,
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ){}

    private function normalize(CalendrierTravail $calendrierTravail): array
    {
        return [
            'id' => $calendrierTravail->getId(),
            'dateJour' => $calendrierTravail->getDateJour()?->format('Y-m-d'),
            'typeJour' => $calendrierTravail->getTypeJour()?->value,
            'estTravaille' => $calendrierTravail->isEstTravaille(),
            'description' => $calendrierTravail->getDescription(),
            'departement' => $calendrierTravail->getDepartement() ? [
                'id' => $calendrierTravail->getDepartement()->getId(),
                'label' => $calendrierTravail->getDepartement()->getLabel(),
            ] : null,
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $calendriersTravail = $this->calendrierTravailRepository->findAll();
        $data = array_map(fn(CalendrierTravail $calendrierTravail) => $this->normalize($calendrierTravail), $calendriersTravail);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $calendrierTravail = $this->calendrierTravailRepository->find($id);
        if (!$calendrierTravail) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }

        return new JsonResponse($this->normalize($calendrierTravail));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['dateJour']) || !isset($data['typeJour'])) {
            return new JsonResponse(['error' => 'dateJour et typeJour sont requis'], 400);
        }

        $typeJour = TypeJour::tryFrom($data['typeJour']);
        if (!$typeJour) {
            return new JsonResponse(['error' => 'typeJour invalide'], 400);
        }

        $calendrierTravail = new CalendrierTravail();
        $calendrierTravail->setDateJour(new \DateTimeImmutable($data['dateJour']));
        $calendrierTravail->setTypeJour($typeJour);
        $calendrierTravail->setEstTravaille($data['estTravaille'] ?? true);
        $calendrierTravail->setDescription($data['description'] ?? null);

        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                return new JsonResponse(['error' => 'Departement non trouvé'], 404);
            }
            $calendrierTravail->setDepartement($departement);
        }

        $this->entityManager->persist($calendrierTravail);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($calendrierTravail), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $calendrierTravail = $this->calendrierTravailRepository->find($id);
        if (!$calendrierTravail) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['dateJour'])) {
            $calendrierTravail->setDateJour(new \DateTimeImmutable($data['dateJour']));
        }

        if (isset($data['typeJour'])) {
            $typeJour = TypeJour::tryFrom($data['typeJour']);
            if (!$typeJour) {
                return new JsonResponse(['error' => 'typeJour invalide'], 400);
            }
            $calendrierTravail->setTypeJour($typeJour);
        }

        if (isset($data['estTravaille'])) {
            $calendrierTravail->setEstTravaille((bool) $data['estTravaille']);
        }

        if (array_key_exists('description', $data)) {
            $calendrierTravail->setDescription($data['description']);
        }

        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if (!$departement) {
                return new JsonResponse(['error' => 'Departement non trouvé'], 404);
            }
            $calendrierTravail->setDepartement($departement);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->normalize($calendrierTravail));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $calendrierTravail = $this->calendrierTravailRepository->find($id);
        if (!$calendrierTravail) {
            return new JsonResponse(['error' => 'Calendrier de travail non trouvé'], 404);
        }

        $this->entityManager->remove($calendrierTravail);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}