<?php

namespace App\Service\Alerte;

use App\Entity\Alerte;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class AlerteRealtimePublisher
{
    public const TOPIC_ALL = 'rh/alertes';
    public const TOPIC_RETARDS = 'rh/alertes/retards';
    public const TOPIC_ABSENCES = 'rh/alertes/absences';

    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function publishCreated(Alerte $alerte): void
    {
        $topics = [self::TOPIC_ALL];

        if ($alerte->getType()?->value === 'retard') {
            $topics[] = self::TOPIC_RETARDS;
        }

        if ($alerte->getType()?->value === 'absence') {
            $topics[] = self::TOPIC_ABSENCES;
        }

        $update = new Update(
            $topics,
            json_encode([
                'event' => 'alerte.created',
                'alerte' => $this->normalizeAlerte($alerte),
            ], JSON_THROW_ON_ERROR),
            false
        );

        $this->hub->publish($update);
    }

    private function normalizeAlerte(Alerte $alerte): array
    {
        $retard = $alerte->getRetard();
        $absence = $alerte->getAbsence();
        $employe = $retard?->getEmploye() ?? $absence?->getEmploye();

        return [
            'id' => $alerte->getId(),
            'type' => $alerte->getType()?->value,
            'statut' => $alerte->getStatut()?->value,
            'message' => $alerte->getMessage(),

            'employe' => $employe ? [
                'id' => $employe->getId(),
                'matricule' => $employe->getMatricule(),
                'nomComplet' => $employe->getNomComplet(),
            ] : null,

            'retard' => $retard ? [
                'id' => $retard->getId(),
                'dateJour' => $retard->getDateJour()?->format('Y-m-d'),
                'heurePrevue' => $retard->getHeurePrevue()?->format('H:i:s'),
                'heureArrivee' => $retard->getHeureArrivee()?->format('H:i:s'),
                'dureeRetardMinutes' => $retard->getDureeRetardMinutes(),
            ] : null,

            'absence' => $absence ? [
                'id' => $absence->getId(),
                'date' => $absence->getDate()?->format('Y-m-d'),
                'statut' => $absence->getStatut()?->value,
                'typeAbsence' => $absence->getTypeAbsence()?->value,
                'periode' => method_exists($absence, 'getLibellePeriode')
                    ? $absence->getLibellePeriode()
                    : null,
            ] : null,
        ];
    }
}