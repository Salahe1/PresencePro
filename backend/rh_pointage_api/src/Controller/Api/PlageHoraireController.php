<?php

namespace App\Controller\Api;

use App\Entity\PlageHoraire;
use App\Repository\PlageHoraireRepository;
use App\Repository\HoraireTravailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/plages-horaires')]
class PlageHoraireController extends AbstractController
{
    public function __construct(
        private PlageHoraireRepository $plageHoraireRepository,
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plagesHoraires = $this->plageHoraireRepository->findAll();
        $data = array_map(fn(PlageHoraire $plageHoraire) => [
            'id' => $plageHoraire->getId(),
            'heureDebut' => $plageHoraire->getHeureDebut()->format('H:i'),
            'heureFin' => $plageHoraire->getHeureFin()->format('H:i'),
            'ordre' => $plageHoraire->getOrdre(),
            'horaireTravail' => [
                'id' => $plageHoraire->getHoraireTravail()->getId(),
                'label' => $plageHoraire->getHoraireTravail()->getLabel(),
            ],
        ], $plagesHoraires);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        if (!$plageHoraire) {
            return new JsonResponse(['error' => 'Plage horaire non trouvée'], 404);
        }

        return new JsonResponse([
            'id' => $plageHoraire->getId(),
            'heureDebut' => $plageHoraire->getHeureDebut()->format('H:i'),
            'heureFin' => $plageHoraire->getHeureFin()->format('H:i'),
            'ordre' => $plageHoraire->getOrdre(),
            'horaireTravail' => [
                'id' => $plageHoraire->getHoraireTravail()->getId(),
                'label' => $plageHoraire->getHoraireTravail()->getLabel(),
            ],
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['heureDebut']) || !isset($data['heureFin']) || !isset($data['ordre']) || !isset($data['horaireTravailId'])) {
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

        return new JsonResponse([
            'id' => $plageHoraire->getId(),
            'heureDebut' => $plageHoraire->getHeureDebut()->format('H:i'),
            'heureFin' => $plageHoraire->getHeureFin()->format('H:i'),
            'ordre' => $plageHoraire->getOrdre(),
            'horaireTravail' => [
                'id' => $plageHoraire->getHoraireTravail()->getId(),
                'label' => $plageHoraire->getHoraireTravail()->getLabel(),
            ],
        ], 201);    
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plageHoraire = $this->plageHoraireRepository->find($id);
        if (!$plageHoraire) {
            return new JsonResponse(['error' => 'Plage horaire non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['heureDebut'])) {
            $plageHoraire->setHeureDebut(new \DateTimeImmutable($data['heureDebut']));
        }
        if (isset($data['heureFin'])) {
            $plageHoraire->setHeureFin(new \DateTimeImmutable($data['heureFin']));
        }
        if (isset($data['ordre'])) {
            $plageHoraire->setOrdre($data['ordre']);
        }
        if (isset($data['horaireTravailId'])) {
            $horaireTravail = $this->horaireTravailRepository->find($data['horaireTravailId']);
            if (!$horaireTravail) {
                return new JsonResponse(['error' => 'Horaire travail non trouvé'], 404);
            }
            $plageHoraire->setHoraireTravail($horaireTravail);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $plageHoraire->getId(),
            'heureDebut' => $plageHoraire->getHeureDebut()->format('H:i'),
            'heureFin' => $plageHoraire->getHeureFin()->format('H:i'),
            'ordre' => $plageHoraire->getOrdre(),
            'horaireTravail' => [
                'id' => $plageHoraire->getHoraireTravail()->getId(),
                'label' => $plageHoraire->getHoraireTravail()->getLabel(),
            ],
        ]);
    }

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