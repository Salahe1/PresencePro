<?php

namespace App\Service\Alerte;

use App\Repository\AlerteRepository;

class AlerteQueryService
{
    public function __construct(private AlerteRepository $alerteRepository){}

    public function getAllAlertes() : array
    {
        $alertes = $this->alerteRepository->findAll();
        return $alertes;
    }

}