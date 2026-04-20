<?php

namespace App\Service\Calendrier;

use App\Entity\Departement;
use App\Enum\TypeJour;
use App\Repository\CalendrierTravailRepository;

class CalendrierTravailResolverService
{
    public function __construct(
        private CalendrierTravailRepository $calendrierTravailRepository
    ) {
    }

    public function resolve(\DateTimeImmutable $date, ?Departement $departement = null): ResolvedCalendrierJour
    {
        if ($departement !== null) {
            $departmentRule = $this->calendrierTravailRepository
                ->findDepartmentRuleForDate($date, $departement);

            if ($departmentRule !== null) {
                return new ResolvedCalendrierJour(
                    $date, $departmentRule->getTypeJour(),
                    $departmentRule->isEstTravaille(), 'departement',
                    $departmentRule
                );
            }
        }

        $globalRule = $this->calendrierTravailRepository->findGlobalRuleForDate($date);

        if ($globalRule !== null) {
            return new ResolvedCalendrierJour(
                $date,  $globalRule->getTypeJour(),
                $globalRule->isEstTravaille(), 'global',
                $globalRule
            );
        }

        return new ResolvedCalendrierJour(
            $date, TypeJour::OUVRABLE, true,
            'implicit_default', null
        );
    }

    public function estJourTravaille(\DateTimeImmutable $date, ?Departement $departement = null): bool
    {
        return $this->resolve($date, $departement)->isEstTravaille();
    }

    public function getTypeJour(\DateTimeImmutable $date, ?Departement $departement = null): TypeJour
    {
        return $this->resolve($date, $departement)->getTypeJour();
    }
}