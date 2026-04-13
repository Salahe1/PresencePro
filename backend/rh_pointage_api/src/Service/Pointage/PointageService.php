<?php

namespace App\Service\Pointage;


use Doctrine\ORM\EntityManagerInterface;
use App\Service\Biometrique\BiometriqueMatchingService;
use App\Service\Retard\RetardDetectionService;
use App\Entity\Pointage;


class PointageService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BiometriqueMatchingService $biometriqueMatchingService,
        private PointageTypeDecider $pointageTypeDecider,
        private RetardDetectionService $retardDetectionService
    ) {
    }

    public function traiterScan(array $biometriquedata, \DateTimeImmutable $timeStamp) : Pointage
    {
        $employe = $this->biometriqueMatchingService->identifierEmploye($biometriquedata);

            if (!$employe) {
             throw new \Exception("Employé non identifié, pointage refusé.");
            }

            if(!$employe->isActif()) {
                throw new \Exception("Employé inactif, pointage refusé.");
            }

            $pointageType = $this->pointageTypeDecider->deciderType($employe, $timeStamp);

            $pointage = new Pointage();
            $pointage->setEmploye($employe);
            $pointage->setType($pointageType);
            $pointage->setTimeStamp($timeStamp);

            $this->entityManager->persist($pointage);

            if ($pointage->isEntree()) {
                // Logique de détection
                $retard = $this->retardDetectionService->creeRetardSiExiste($pointage);

               if($retard !== null){
                    $this->entityManager->persist($retard);
                }
            }

             $this->entityManager->flush();                    

        return $pointage;
    }

}
