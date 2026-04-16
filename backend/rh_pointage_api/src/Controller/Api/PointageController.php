<?php

namespace App\Controller\Api;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Pointage\PointageQueryService;
use App\Service\Pointage\PointageScanService;
use App\Entity\Pointage;


#[Route('/api/pointages')]
class PointageController extends AbstractController
{
    public function __construct(private PointageQueryService $pointageQueryService
                                ,private PointageScanService $pointageScanService ){}

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
            $pointageResult = $this->pointageScanService->traiterScan($dataBiometrique, $timeStamp);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }    
        $ResponseData = $this->serializePointage($pointageResult);

        return new JsonResponse($ResponseData, 201);
    }

    #[Route('', methods: ['GET'])]
    public function pointages() : JsonResponse
    {
        $pointages = $this->pointageQueryService->getAllPointages();
        $responseData = array_map(fn(Pointage $pointage) => $this->serializePointage($pointage), $pointages);

        return new JsonResponse($responseData);
    }


    #[Route('/{id}', methods: ['GET'])]
    public function getPointage(int $id) : JsonResponse
    {
        $pointage = $this->pointageQueryService->getPointageById($id);
        if (!$pointage) {
            return new JsonResponse(['error' => 'Pointage non trouvé'], 404);
        }

        $responseData = $this->serializePointage($pointage);

        return new JsonResponse($responseData);
    }

    #[Route('/employe/{id}', methods: ['GET'])]
    public function getEmployePointages(int $id) : JsonResponse
    {
        $pointages = $this->pointageQueryService->getPointagesByEmployeId($id);
        $responseData = array_map(fn(Pointage $pointage) => $this->serializePointage($pointage), $pointages);

        return new JsonResponse($responseData);
    }

    #[Route('/date/{date}', methods: ['GET'])]
    public function getPointagesByDate(string $date): JsonResponse
    {
     $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
     $errors = \DateTimeImmutable::getLastErrors();

     if ( $dateObj === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))  ){

        return new JsonResponse(['error' => 'Format de date invalide. Utilisez Y-m-d'], 400);

       }

        $pointages = $this->pointageQueryService->getPointagesByDate($dateObj);
        $responseData = array_map(fn(Pointage $pointage) => $this->serializePointage($pointage), $pointages);

        return new JsonResponse($responseData);
    }

    private function serializePointage(Pointage $pointage): array
    {
        return [
        'id' => $pointage->getId(),
        'employe' => $pointage->getEmploye()->getNomComplet(),
        'type' => $pointage->getType()?->value,
        'timeStamp' => $pointage->getTimeStamp()->format('Y-m-d H:i:s'),
        ];
    }
}