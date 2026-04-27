<?php

namespace App\Controller\Api;

use App\Entity\PlageHoraire;
use App\Exception\ApiException;
use App\Repository\HoraireTravailRepository;
use App\Repository\PlageHoraireRepository;
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
    use ApiControllerHelperTrait;

    public function __construct(
        private PlageHoraireRepository $plageHoraireRepository,
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $entityManager
    ) {}

    private function normalize(PlageHoraire $plage): array
    {
        return [
            'id' => $plage->getId(),
            'heureDebut' => $plage->getHeureDebut()->format('H:i'),
            'heureFin' => $plage->getHeureFin()->format('H:i'),
            'ordre' => $plage->getOrdre(),
            'horaireTravail' => [
                'id' => $plage->getHoraireTravail()->getId(),
                'label' => $plage->getHoraireTravail()->getLabel(),
            ],
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plagesHoraires = $this->plageHoraireRepository->findAll();

        return new JsonResponse(array_map(
            fn(PlageHoraire $p) => $this->normalize($p),
            $plagesHoraires
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        $this->assertFound($plageHoraire, 'Plage horaire non trouvée.', 'PLAGE_HORAIRE_NOT_FOUND');

        return new JsonResponse($this->normalize($plageHoraire));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $this->requireFields($data, ['heureDebut', 'heureFin', 'ordre', 'horaireTravailId']);

        if (!is_int($data['ordre']) && !ctype_digit((string) $data['ordre'])) {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422,
                'VALIDATION_ERROR',
                ['ordre' => ['Ce champ doit être un entier.']]
            );
        }

        $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
        $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');

        $plageHoraire = new PlageHoraire();
        $plageHoraire->setHeureDebut($this->parseTime($data['heureDebut'], 'heureDebut'));
        $plageHoraire->setHeureFin($this->parseTime($data['heureFin'], 'heureFin'));
        $plageHoraire->setOrdre((int) $data['ordre']);
        $plageHoraire->setHoraireTravail($horaireTravail);

        $this->entityManager->persist($plageHoraire);
        $this->entityManager->flush();

        return new JsonResponse($this->normalize($plageHoraire), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        $this->assertFound($plageHoraire, 'Plage horaire non trouvée.', 'PLAGE_HORAIRE_NOT_FOUND');

        $data = $this->parseJson($request);

        if (array_key_exists('heureDebut', $data)) {
            $plageHoraire->setHeureDebut($this->parseTime($data['heureDebut'], 'heureDebut'));
        }
        if (array_key_exists('heureFin', $data)) {
            $plageHoraire->setHeureFin($this->parseTime($data['heureFin'], 'heureFin'));
        }
        if (array_key_exists('ordre', $data)) {
            if (!is_int($data['ordre']) && !ctype_digit((string) $data['ordre'])) {
                throw new ApiException(
                    'Les données envoyées sont invalides.',
                    422,
                    'VALIDATION_ERROR',
                    ['ordre' => ['Ce champ doit être un entier.']]
                );
            }
            $plageHoraire->setOrdre((int) $data['ordre']);
        }
        if (array_key_exists('horaireTravailId', $data)) {
            $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
            $this->assertFound($horaireTravail, 'Horaire de travail non trouvé.', 'HORAIRE_TRAVAIL_NOT_FOUND');
            $plageHoraire->setHoraireTravail($horaireTravail);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->normalize($plageHoraire));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        $this->assertFound($plageHoraire, 'Plage horaire non trouvée.', 'PLAGE_HORAIRE_NOT_FOUND');

        $this->entityManager->remove($plageHoraire);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
