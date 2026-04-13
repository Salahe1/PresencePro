<?php

namespace App\Service\Pointage;

use App\Entity\Employe;
use App\Entity\Pointage;
use App\Enum\TypePointage;

class PointageTypeDecider
{
  public function deciderType(Employe $employe, \DateTimeImmutable $timeStamp): TypePointage
    {
     $date = $timeStamp->format('Y-m-d');

     $pointagesDuJour = $employe->getPointages()->filter(
        function (Pointage $pointage) use ($date) {
            return $pointage->getTimeStamp()->format('Y-m-d') === $date;
             })->toArray();

     usort($pointagesDuJour, function (Pointage $a, Pointage $b) {
         return $a->getTimeStamp()->getTimestamp() <=> $b->getTimeStamp()->getTimestamp();
        });

     if (empty($pointagesDuJour)) {   return TypePointage::Entre; }

        $dernierPointage = end($pointagesDuJour);

     if ($dernierPointage->isEntree()) { return TypePointage::Sortie; }

         return TypePointage::Entre;
    }       

}
