<?php

namespace App\Controller\Api;

use App\Entity\HoraireTravail;
use App\Repository\HoraireTravailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/api/horaires-travail')]
class HoraireTravailController extends AbstractController
{
    public function __construct(
        private HoraireTravailRepository $horaireTravailRepository,
        private EntityManagerInterface $managerInterface    
    ) {}

    #[Route('', methods: ['GET'])]
    public function index():JsonResponse
    {
        $horairesTravail = $this->horaireTravailRepository->findAll();
        $data = array_map(fn(HoraireTravail $horaireTravail) => [
            'id' => $horaireTravail->getId(),
            'label' => $horaireTravail->getLabel(),
            'toleranceRetard' => $horaireTravail->getToleranceRetard()?->format('H:i'),
            'plagesHoraires' => array_map(fn($plage) => [
                'id' => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin' => $plage->getHeureFin()->format('H:i'),
                'ordre' => $plage->getOrdre(),
            ], $horaireTravail->getPlagesHoraires()->toArray()),
        ], $horairesTravail);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);
        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }

        return new JsonResponse([
            'id' => $horaireTravail->getId(),
            'label' => $horaireTravail->getLabel(),
            'toleranceRetard' => $horaireTravail->getToleranceRetard()?->format('H:i'),
            'plagesHoraires' => array_map(fn($plage) => [
                'id' => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin' => $plage->getHeureFin()->format('H:i'),
                'ordre' => $plage->getOrdre(),
            ], $horaireTravail->getPlagesHoraires()->toArray()),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['label']) || !isset($data['toleranceRetard'])) {
            return new JsonResponse(['error' => 'Label et toleranceRetard sont requises'], 400);
        }

        $horaireTravail = new HoraireTravail();
        $horaireTravail->setLabel($data['label']);
        $horaireTravail->setToleranceRetard(new \DateTimeImmutable($data['toleranceRetard'])); 

        $this->managerInterface->persist($horaireTravail);
        $this->managerInterface->flush();

        return new JsonResponse([
            'id' => $horaireTravail->getId(),
            'label' => $horaireTravail->getLabel(),
            'toleranceRetard' => $horaireTravail->getToleranceRetard()?->format('H:i'),
            'plagesHoraires' => array_map(fn($plage) => [
                'id' => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin' => $plage->getHeureFin()->format('H:i'),
                'ordre' => $plage->getOrdre(),
            ], $horaireTravail->getPlagesHoraires()->toArray()),
        ], 201);   

    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);

        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['label'])) {
            $horaireTravail->setLabel($data['label']);
        }
        if (isset($data['toleranceRetard'])) {
            $horaireTravail->setToleranceRetard(new \DateTimeImmutable($data['toleranceRetard']));
        }

        $this->managerInterface->flush();

        return new JsonResponse([
            'id' => $horaireTravail->getId(),
            'label' => $horaireTravail->getLabel(),
            'toleranceRetard' => $horaireTravail->getToleranceRetard()?->format('H:i'),
            'plagesHoraires' => array_map(fn($plage) => [
                'id' => $plage->getId(),
                'heureDebut' => $plage->getHeureDebut()->format('H:i'),
                'heureFin' => $plage->getHeureFin()->format('H:i'),
                'ordre' => $plage->getOrdre(),
            ], $horaireTravail->getPlagesHoraires()->toArray()),
        ]); 
    }

    #[Route('/{id}', methods: ['Delete'])]
    public function delete(int $id): JsonResponse
    {
        $horaireTravail = $this->horaireTravailRepository->find($id);

        if (!$horaireTravail) {
            return new JsonResponse(['error' => 'Horaire de travail non trouvé'], 404);
        }

        $this->managerInterface->remove($horaireTravail);
        $this->managerInterface->flush();

        return new JsonResponse(null, 204);
    }
}
