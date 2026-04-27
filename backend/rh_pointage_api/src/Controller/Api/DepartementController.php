<?php

namespace App\Controller\Api;

use App\Entity\Departement;
use App\Exception\ApiException;
use App\Repository\DepartementRepository;
use App\Repository\HoraireTravailRepository;
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
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    private function normalize(Departement $departement): array
    {
        return [
            'id' => $departement->getId(),
            'label' => $departement->getLabel(),
            'horaireTravail' => $departement->getHoraireTravail() ? [
                'id' => $departement->getHoraireTravail()->getId(),
                'label' => $departement->getHoraireTravail()->getLabel(),
            ] : null,
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $departements = $this->departementRepository->findAll();
        $data = array_map(fn(Departement $d) => $this->normalize($d), $departements);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $departement = $this->departementRepository->find($id);
        $this->assertFound($departement, 'Département non trouvé.', 'DEPARTEMENT_NOT_FOUND');

        return new JsonResponse($this->normalize($departement));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $this->requireFields($data, ['label', 'horaireTravailId']);

        $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
        $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');

        $departement = new Departement();
        $departement->setLabel($data['label']);
        $departement->setHoraireTravail($horaireTravail);

        $this->entityManager->persist($departement);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($departement), 201);
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

        if (array_key_exists('horaireTravailId', $data)) {
            if ($data['horaireTravailId'] === null || $data['horaireTravailId'] === '') {
                throw new ApiException(
                    'Les données envoyées sont invalides.',
                    422,
                    'VALIDATION_ERROR',
                    ['horaireTravailId' => ['Ce champ ne peut pas être vide.']]
                );
            }

            $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
            $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');
            $departement->setHoraireTravail($horaireTravail);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->normalize($departement));
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
