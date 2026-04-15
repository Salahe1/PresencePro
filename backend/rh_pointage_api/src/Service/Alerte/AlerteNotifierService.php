<?php

namespace App\Service\Alerte;

use App\Entity\Alerte;
use App\Entity\Retard;
use App\Enum\TypeAlerte;

class AlerteNotifierService 
{
    public function declencherAlerte (Retard $retard) : Alerte
    {
        $nomComplet = $retard->getEmploye()->getNomComplet();
        $matricule = $retard->getEmploye()->getMatricule();
        $date = $retard->getDateJour()->format('Y-m-d');
        $hPrevue = $retard->getHeurePrevue()->format('H:i');
        $hArrive = $retard->getHeureArrivee()->format('H:i');

        $message = sprintf(
            "Employé %s [Matricule %s] en retard le %s. Heure prévue: %s, heure d'arrivée: %s.",
            $nomComplet,  $matricule,  $date,  $hPrevue, $hArrive );

        $alerte = new Alerte();
        $alerte->setType(TypeAlerte::RETARD);
        $alerte->setMessage($message);
        $alerte->setRetard($retard);

        return $alerte;
        
    }
}