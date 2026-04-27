<?php

namespace App\Controller\Api;

use App\Entity\Pointage;
use App\Exception\ApiException;
use App\Service\Pointage\PointageQueryService;
use App\Service\Pointage\PointageScanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/api/pointages')]
class PointageController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private PointageQueryService $pointageQueryService,
        private PointageScanService $pointageScanService
    ) {}

    #[Route('/scan', methods: ['POST'])]
    public function scan(Request $request): JsonResponse
    {
        $payload = $this->parseJson($request);

        $details = [];

        if (!array_key_exists('dataBiometrique', $payload) || !is_array($payload['dataBiometrique'])) {
            $details['dataBiometrique'] = ['Ce champ est obligatoire et doit être un tableau.'];
        }

        if (!array_key_exists('timeStamp', $payload) || $payload['timeStamp'] === null || $payload['timeStamp'] === '') {
            $details['timeStamp'] = ['Ce champ est obligatoire.'];
        }

        if (!empty($details)) {
            throw new ApiException(
                'Les données envoyées sont invalides.',
                422, 'VALIDATION_ERROR', $details
            );
        }

        $timeStamp = $this->parseDateTime($payload['timeStamp'], 'timeStamp');
        $pointage = $this->pointageScanService->traiterScan($payload['dataBiometrique'], $timeStamp);

        return new JsonResponse($this->serializePointage($pointage), 201);
    }

    #[Route('', methods: ['GET'])]
    public function pointages(): JsonResponse
    {
        $pointages = $this->pointageQueryService->getAllPointages();
        $responseData = array_map(fn(Pointage $pointage) => $this->serializePointage($pointage), $pointages);

        return new JsonResponse($responseData);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getPointage(int $id): JsonResponse
    {
        $pointage = $this->pointageQueryService->getPointageById($id);
        $this->assertFound($pointage, 'Pointage non trouvé.', 'POINTAGE_NOT_FOUND');

        return new JsonResponse($this->serializePointage($pointage));
    }

    #[Route('/employe/{id}', methods: ['GET'])]
    public function getEmployePointages(int $id): JsonResponse
    {
        $pointages = $this->pointageQueryService->getPointagesByEmployeId($id);
        $responseData = array_map(fn(Pointage $pointage) => $this->serializePointage($pointage), $pointages);

        return new JsonResponse($responseData);
    }

    #[Route('/date/{date}', methods: ['GET'])]
    public function getPointagesByDate(string $date): JsonResponse
    {
        $dateObj = $this->parseDate($date, 'date', 'Y-m-d');
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
