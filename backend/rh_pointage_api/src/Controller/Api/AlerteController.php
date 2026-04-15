<?php

namespace App\Controller\Api;

use App\Service\Alerte\AlerteQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Alerte;

#[Route('/api/alertes')]
class AlerteController extends AbstractController 
{
    public function __construct(private AlerteQueryService $alerteQueryService){}

    #[Route('', methods:['GET'])]
    public function listerAlertes () : JsonResponse
    {
        $alertes = $this->alerteQueryService->getAllAlertes();
        $responseData = array_map(
            fn(Alerte $alerte) => $this->serializeAlerte($alerte),
            $alertes
        );
        return new JsonResponse($responseData);
    }

    
    private function serializeAlerte(Alerte $alerte): array
    {
        $retard = $alerte->getRetard();
        $employe = $retard?->getEmploye();
        $traitePar = $alerte->getTraitePar();

        return [
            'id' => $alerte->getId(),
            'type' => $alerte->getType()?->value,
            'statut' => $alerte->getStatut()?->value,
            'message' => $alerte->getMessage(),

            'retard' => $retard ? [
                'id' => $retard->getId(),
                'dateJour' => $retard->getDateJour()?->format('Y-m-d'),
                'heurePrevue' => $retard->getHeurePrevue()?->format('H:i:s'),
                'heureArrivee' => $retard->getHeureArrivee()?->format('H:i:s'),
                'dureeRetardMinutes' => $retard->getDureeRetardMinutes(),
                'justifie' => $retard->isJustifie(),
                'commentaire' => $retard->getCommentaire(),
            ] : null,

            'employe' => $employe ? [
                'id' => $employe->getId(),
                'nomComplet' => $employe->getNomComplet(),
                'matricule' => $employe->getMatricule(),
            ] : null,

            'traitePar' => $traitePar ? [
                'id' => $traitePar->getId(),
                'nomComplet' => $traitePar->getNomComplet(),
                'matricule' => $traitePar->getMatricule(),
            ] : null,
            ];
    }
}