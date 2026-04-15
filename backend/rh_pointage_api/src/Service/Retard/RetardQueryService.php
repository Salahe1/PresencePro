<?php

namespace App\Service\Retard;

use App\Entity\Retard;
use App\Repository\RetardRepository;

class RetardQueryService 
{
    public function __construct (private RetardRepository $retardRepository ){}

    public function getAllRetards() : array
    {
        return $this->retardRepository->findAllByDateDesc();
    }   

    public function getRetardsByDate(\DateTimeInterface $date) : array
    {
        return $this->retardRepository->findByDate($date);
    }

    public function getRetardsByEmploye(int $employeId) : array
    {
        return $this->retardRepository->findByEmploye($employeId);
    }

    public function getRetardById(int $id): ?Retard
    {
        return $this->retardRepository->find($id);
    }
}