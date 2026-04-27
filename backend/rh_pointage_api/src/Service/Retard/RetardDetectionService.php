<?php

namespace App\Service\Retard;

use App\Repository\PointageRepository;
use App\Entity\PlageHoraire;
use App\Entity\Pointage;
use App\Entity\Retard;

class RetardDetectionService
{
    public function __construct(private PointageRepository $pointageRepository){}

    public function creeRetardSiExiste(Pointage $pointage): ?Retard
    {
        if (!$pointage->isEntree()) { return null; }

        $employe = $pointage->getEmploye();


        $departement = $employe->getDepartement();
        if (!$departement) { return null; }

        $horaire = $departement->getHoraireTravail();
        if (!$horaire) {  return null; }

        $tolerance = $horaire->getToleranceRetard();
        if (!$tolerance) {  return null; }

        $plages = array_values($horaire->getPlagesHoraires()->toArray());

        $premierePlage = $plages[0] ?? null;

        $deuxiemePlage = $plages[1] ?? null;

        if (!$premierePlage) {  return null;}

        $scanAt = $pointage->getTimeStamp();
        if (!$scanAt) { return null; }

        $debutPremierePlage = new \DateTimeImmutable(  $scanAt->format('Y-m-d') . ' ' . $premierePlage->getHeureDebut()->format('H:i:s') );
        $finPremierePlage = new \DateTimeImmutable(  $scanAt->format('Y-m-d') . ' ' . $premierePlage->getHeureFin()->format('H:i:s') );

        $heurePrevue = null;
        
        $aujourdhuiPointages = $this->pointageRepository->findTodayPointagesByEmploye($employe, $pointage->getTimeStamp());

        if ($scanAt >= $debutPremierePlage && $scanAt <= $finPremierePlage) {
            $heurePrevue = $debutPremierePlage;

            $verificationPremierEntre = $this->verifierPremierEntre($aujourdhuiPointages, $heurePrevue, $finPremierePlage);

            if ($verificationPremierEntre) { return null; }

        } elseif ($deuxiemePlage) {
            $debutDeuxiemePlage = new \DateTimeImmutable( $scanAt->format('Y-m-d') . ' ' . $deuxiemePlage->getHeureDebut()->format('H:i:s') );

            $finDeuxiemePlage = new \DateTimeImmutable( $scanAt->format('Y-m-d') . ' ' . $deuxiemePlage->getHeureFin()->format('H:i:s') );

            if ($scanAt >= $debutDeuxiemePlage && $scanAt <= $finDeuxiemePlage) {
                $heurePrevue = $debutDeuxiemePlage;

                $verificationPremierEntre = $this->verifierPremierEntre($aujourdhuiPointages, $heurePrevue, $finDeuxiemePlage);

                if ($verificationPremierEntre) { return null;  }
            }
        }

        if (!$heurePrevue) { return null; }

        $minutesTolerance = ((int) $tolerance->format('H') * 60) + (int) $tolerance->format('i');
        $limiteRetard = $heurePrevue->modify("+{$minutesTolerance} minutes");

        if ($scanAt <= $limiteRetard) { return null; }

        $retard = new Retard();
        $retard->setDateJour(new \DateTimeImmutable($scanAt->format('Y-m-d')));
        $retard->setHeurePrevue($heurePrevue);
        $retard->setHeureArrivee($scanAt);
        $retard->setPointage($pointage);
        $retard->setEmploye($employe);

        return $retard;
    }

    private function verifierPremierEntre($collection, $debutH, $finH): bool
    {
        return $collection->exists(
            fn($_, Pointage $p) => $p->getType()?->value === 'entre' && $p->getTimeStamp() >= $debutH && $p->getTimeStamp() <= $finH
        );
    }
}
