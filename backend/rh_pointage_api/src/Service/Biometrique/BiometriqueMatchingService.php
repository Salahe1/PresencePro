<?php

namespace App\Service\Biometrique;

use App\Repository\EmployeRepository;
use App\Entity\Employe;

class BiometriqueMatchingService
{

    public function __construct(
        private EmployeRepository $employeRepository
    ){}

    public function identifierEmploye(array $biometriquedata): ?Employe
    {
    foreach ($this->employeRepository->findAll() as $employe) {
        if ($employe->getBiometriqueData() === $biometriquedata)  { // TODO: remplacer l'égalité stricte par une comparaison de distance biométrique avec seuil.
            return $employe;
        }
    }

    return null;
    }
/*     public function getEmployesBiometriqueData()
    {
        $employes = $this->employeRepository->findAll();
        $employesBiometriqueData = [];

        foreach ($employes as $employesBiometrique) {
            $employesBiometriqueData[] = [
                'id' => $employesBiometrique->getId(),
                'biometriqueData' => $employesBiometrique->getBiometriqueData(),
            ];
        }

        return $employesBiometriqueData;
    }

    public function identifierEmploye(array $biometriquedata): ?Employe
    {
        foreach ($this->getEmployesBiometriqueData() as $employe) {

            if ($employe['biometriqueData'] == $biometriquedata) {
                return $employe;
            }

        }
        return null;
    } */




}