<?php

namespace App\Controller\Api;

use App\Entity\Retard;
use App\Service\Retard\RetardQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/retards')]
class RetardController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private RetardQueryService $retardQueryService
    ) {}

    #[Route('', methods: ['GET'])]
    public function tousRetards(): JsonResponse
    {
        $retards = $this->retardQueryService->getAllRetards();
        $responseData = array_map(
            fn(Retard $retard) => $this->serializeRetard($retard),
            $retards
        );

        return new JsonResponse($responseData);
    }

    #[Route('/date/{date}', methods: ['GET'])]
    public function retardsDuJour(string $date): JsonResponse
    {
        $dateObj = $this->parseDate($date, 'date', 'Y-m-d');
        $retards = $this->retardQueryService->getRetardsByDate($dateObj);

        $responseData = array_map(
            fn(Retard $retard) => $this->serializeRetard($retard),
            $retards
        );

        return new JsonResponse($responseData);
    }

    #[Route('/employe/{id}', methods: ['GET'])]
    public function retardsEmploye(int $id): JsonResponse
    {
        $retards = $this->retardQueryService->getRetardsByEmploye($id);

        $responseData = array_map(
            fn(Retard $retard) => $this->serializeRetard($retard),
            $retards
        );

        return new JsonResponse($responseData);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function retard(int $id): JsonResponse
    {
        $retard = $this->retardQueryService->getRetardById($id);
        $this->assertFound($retard, 'Retard non trouvé.', 'RETARD_NOT_FOUND');

        return new JsonResponse($this->serializeRetard($retard));
    }

    private function serializeRetard(Retard $retard): array
    {
        return [
            'id' => $retard->getId(),
            'dateJour' => $retard->getDateJour()?->format('Y-m-d'),
            'heurePrevue' => $retard->getHeurePrevue()?->format('H:i:s'),
            'heureArrivee' => $retard->getHeureArrivee()?->format('H:i:s'),
            'justifie' => $retard->isJustifie(),
            'employeNom' => $retard->getEmploye()->getNomComplet(),
            'employeMatricule' => $retard->getEmploye()->getMatricule(),
            'dureeRetardMinutes' => $retard->getDureeRetardMinutes(),
            'commentaire' => $retard->getCommentaire(),
        ];
    }
}
