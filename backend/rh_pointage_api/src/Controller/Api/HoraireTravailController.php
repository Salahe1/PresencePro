<?php

namespace App\Controller\Api;

use App\Entity\HoraireTravail;
use App\Exception\ApiException;
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
    use ApiControllerHelperTrait;

    public function __construct(
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $managerInterface
    ) {}

    private function normalize(HoraireTravail $h): array
    {
        return [
            'id' => $h->getId(),
            'label' => $h->getLabel(),
            'toleranceRetard' => $h->getToleranceRetard()?->format('H:i'),
            'plagesHoraires' => array_map(fn($plage) => [
                'id' => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin' => $plage->getHeureFin()->format('H:i'),
                'ordre' => $plage->getOrdre(),
            ], $h->getPlagesHoraires()->toArray()),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $horairesTravail = $this->horaireTravailRepository->findAll();

        return new JsonResponse(array_map(
            fn(HoraireTravail $h) => $this->normalize($h),
            $horairesTravail
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');

        return new JsonResponse($this->normalize($horaireTravail));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $this->requireFields($data, ['label', 'toleranceRetard']);

        $horaireTravail = new HoraireTravail();
        $horaireTravail->setLabel($data['label']);
        $horaireTravail->setToleranceRetard($this->parseTime($data['toleranceRetard'], 'toleranceRetard'));

        $this->managerInterface->persist($horaireTravail);
        $this->managerInterface->flush();

        return new JsonResponse($this->normalize($horaireTravail), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');

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
            $horaireTravail->setLabel($data['label']);
        }

        if (array_key_exists('toleranceRetard', $data)) {
            $horaireTravail->setToleranceRetard($this->parseTime($data['toleranceRetard'], 'toleranceRetard'));
        }

        $this->managerInterface->flush();

        return new JsonResponse($this->normalize($horaireTravail));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');

        $this->managerInterface->remove($horaireTravail);
        $this->managerInterface->flush();

        return new JsonResponse(null, 204);
    }
}
