<?php

namespace App\Service\Pointage;

use App\Entity\Pointage;
use App\Exception\ApiException;
use App\Repository\PointageRepository;
use App\Service\Alerte\AlerteNotifierService;
use App\Service\Biometrique\BiometriqueMatchingService;
use App\Service\Retard\RetardDetectionService;
use Doctrine\ORM\EntityManagerInterface;

class PointageScanService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BiometriqueMatchingService $biometriqueMatchingService,
        private PointageTypeDecider $pointageTypeDecider,
        private RetardDetectionService $retardDetectionService,
        private PointageRepository $pointageRepository,
        private AlerteNotifierService $alerteNotifierService
    ) {}

    public function traiterScan(array $biometriquedata, \DateTimeImmutable $timeStamp): Pointage
    {
        $employe = $this->biometriqueMatchingService->identifierEmploye($biometriquedata);

        if (!$employe) {
            throw new ApiException(
                'Employé non identifié, pointage refusé.',
                404,
                'EMPLOYE_NON_IDENTIFIE'
            );
        }

        if (!$employe->isActif()) {
            throw new ApiException(
                'Employé inactif, pointage refusé.',
                403,
                'EMPLOYE_INACTIF'
            );
        }

        $pointageType = $this->pointageTypeDecider->deciderType($employe, $timeStamp);

        $pointage = new Pointage();
        $pointage->setEmploye($employe);
        $pointage->setType($pointageType);
        $pointage->setTimeStamp($timeStamp);

        if ($pointage->isEntree()) {
            $retard = $this->retardDetectionService->creeRetardSiExiste($pointage);

            if ($retard !== null) {
                $this->entityManager->persist($retard);

                $alerte = $this->alerteNotifierService->declencherAlerte($retard);
                $this->entityManager->persist($alerte);
            }
        }

        $this->entityManager->persist($pointage);
        $this->entityManager->flush();

        return $pointage;
    }
}
