<?php

namespace App\Service\Alerte;

use App\Entity\Absence;
use App\Entity\Alerte;
use App\Entity\Retard;
use App\Enum\TypeAlerte;
use App\Enum\StatutAlerte;

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


    public function declencherAlerteAbsence(Absence $absence): Alerte
    {
        $employe = $absence->getEmploye();
        
        if (!$employe) {
            throw new \InvalidArgumentException("Impossible de créer une alerte : l'absence n'a pas d'employé.");
        }

        if (!$absence->getDate()) {
            throw new \InvalidArgumentException("Impossible de Créer une alerte : l'absence n'a pas de date");
        }

        $message = sprintf("Employé %s [Matricule %s] absent le %s."
                          ,$employe->getNomComplet(),
                           $employe->getMatricule(),
                           $absence->getDate()->format('Y-m-d'));
        
        $alerte = new Alerte();
        $alerte->setType(TypeAlerte::ABSENCE);
        $alerte->setMessage($message);
        $alerte->setAbsence($absence);

        return $alerte;
    }
}