<?php

namespace App\Service\Pointage;

use App\Repository\PointageRepository;
use App\Entity\Pointage;



class PointageQueryService
{

    public function __construct(
        private PointageRepository $pointageRepository
    ) {}


    public function getAllPointages(): array
    {
    return $this->pointageRepository->findAllOrderByTimeStampDesc();
    }

    public function getPointageById(int $id) : ?Pointage
    {
        return $this->pointageRepository->find($id);
    }

    public function getPointagesByEmployeId(int $employeId) : array
    {
        return $this->pointageRepository->findByEmployeId($employeId);
    }

    public function getPointagesByDate(\DateTimeInterface $date) : array
    {
        return $this->pointageRepository->findByDate($date);
    }
}