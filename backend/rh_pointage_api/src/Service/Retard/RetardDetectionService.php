<?php

namespace App\Service\Retard;

use App\Entity\Pointage;
use App\Entity\Retard;
use App\Entity\PlageHoraire;

class RetardDetectionService
{
    public function creeRetardSiExiste(Pointage $pointage): ?Retard
    {
        if (!$pointage->isEntree()) { return null; }

        $employe = $pointage->getEmploye();

        $departement = $employe->getDepartement();
        if (!$departement) { return null; }

        $horaire = $departement->getHorairesTravail()->first();
        if (!$horaire) { return null;}

        $tolerance = $horaire->getToleranceRetard();
        if (!$tolerance) { return null; }

        $plages = array_values($horaire->getPlagesHoraires()->toArray());

        //** @var PlageHoraire|null $premierePlage */
        $premierePlage = $plages[0] ?? null;

        //** @var PlageHoraire|null $deuxiemePlage */
        $deuxiemePlage = $plages[1] ?? null;

        if (!$premierePlage) { return null;  }

        $scanAt = $pointage->getTimeStamp();
        if (!$scanAt) {  return null; }

        $debutPremierePlage = new \DateTimeImmutable(
            $scanAt->format('Y-m-d') . ' ' . $premierePlage->getHeureDebut()->format('H:i:s')
        );

        $finPremierePlage = new \DateTimeImmutable(
            $scanAt->format('Y-m-d') . ' ' . $premierePlage->getHeureFin()->format('H:i:s')
        );

        $heurePrevue = null;

        if ($scanAt >= $debutPremierePlage && $scanAt <= $finPremierePlage) {
            $heurePrevue = $debutPremierePlage;
        } elseif ($deuxiemePlage) {
            $debutDeuxiemePlage = new \DateTimeImmutable(
                $scanAt->format('Y-m-d') . ' ' . $deuxiemePlage->getHeureDebut()->format('H:i:s')
            );

            $finDeuxiemePlage = new \DateTimeImmutable(
                $scanAt->format('Y-m-d') . ' ' . $deuxiemePlage->getHeureFin()->format('H:i:s')
            );

            if ($scanAt >= $debutDeuxiemePlage && $scanAt <= $finDeuxiemePlage) {
                $heurePrevue = $debutDeuxiemePlage;
            }
        }

        if (!$heurePrevue) {
            return null;
        }

        $minutesTolerance =
            ((int) $tolerance->format('H') * 60) +
            (int) $tolerance->format('i');

        $limiteRetard = $heurePrevue->modify("+{$minutesTolerance} minutes");

        if ($scanAt <= $limiteRetard) {
            return null;
        }

        $retard = new Retard();
        $retard->setDateJour(new \DateTimeImmutable($scanAt->format('Y-m-d')));
        $retard->setHeurePrevue($heurePrevue);
        $retard->setHeureArrivee($scanAt);
        $retard->setPointage($pointage);
        $retard->setEmploye($employe);

        return $retard;
    }
}