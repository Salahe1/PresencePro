<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Pointage\PointageService;



#[Route('/api/pointage')]
class PointageController extends AbstractController
{
    public function __construct(private PointageService $pointageService){}

    #[Route('/scan', methods: ['POST'])]
    public function scan(Request $request) : JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        $dataBiometrique = $payload['dataBiometrique'] ?? null;
        $timeStampRaw = $payload['timeStamp'] ?? null;

        if ($dataBiometrique === null || $timeStampRaw === null) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        try {
            $timeStamp = new \DateTimeImmutable($timeStampRaw);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], 400);
        }

        try {
            $pointageResult = $this->pointageService->traiterScan($dataBiometrique, $timeStamp);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }    
        $ResponseData = [
            'employe' => $pointageResult->getEmploye()->getNomComplet(),
            'type' => $pointageResult->getType()?->value,
            'timeStamp' => $pointageResult->getTimeStamp()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($ResponseData, 201);
    }
}