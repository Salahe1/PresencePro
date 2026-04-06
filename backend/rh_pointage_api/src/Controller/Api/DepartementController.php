<?php

namespace App\Controller\Api;

use App\Entity\Departement;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/departements')]
class DepartementController extends AbstractController
{
    public function __construct(
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $departements = $this->departementRepository->findAll();
        $data = array_map(fn(Departement $departement) => [
            'id' => $departement->getId(),
            'label' => $departement->getLabel(),
        ], $departements);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        if (!$departement) {
            return new JsonResponse(['error' => 'Departement not found'], 404);
        }

        return new JsonResponse([
            'id' => $departement->getId(),
            'label' => $departement->getLabel(),
        ]);
    }

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

        return new JsonResponse([
            'id' => $departement->getId(),
            'label' => $departement->getLabel(),
        ], 201);
    }

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

        return new JsonResponse([
            'id' => $departement->getId(),
            'label' => $departement->getLabel(),
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        if (!$departement) {
            return new JsonResponse(['error' => 'Departement not found'], 404);
        }

        $this->entityManager->remove($departement);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Departement deleted'], 204);
    }
}