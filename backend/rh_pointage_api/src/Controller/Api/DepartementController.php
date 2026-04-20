<?php

namespace App\Controller\Api;

use App\Entity\Departement;
use App\Exception\ApiException;
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
    use ApiControllerHelperTrait;

    public function __construct(
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $departements = $this->departementRepository->findAll();
        $data = array_map(fn(Departement $d) => [
            'id' => $d->getId(),
            'label' => $d->getLabel(),
        ], $departements);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');

        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $this->requireFields($data, ['label']);

        $departement = new Departement();
        $departement->setLabel($data['label']);

        $this->entityManager->persist($departement);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');

        $data = $this->parseJson($request);

        if (array_key_exists('label', $data)) {
            if ($data['label'] === null || $data['label'] === '') {
                throw new ApiException(
                    'Les données envoyées sont invalides.',
                    422,
                    'VALIDATION_ERROR',
                    ['label' => ['Ce champ ne peut pas être vide.']]
                );
            }

            $departement->setLabel($data['label']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['id' => $departement->getId(), 'label' => $departement->getLabel()]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');

        $this->entityManager->remove($departement);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
