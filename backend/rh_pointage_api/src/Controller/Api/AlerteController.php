<?php

namespace App\Controller\Api;

use App\Entity\Alerte;
use App\Service\Alerte\AlerteQueryService;
use App\Service\Alerte\AlerteCommandService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/api/alertes')]
class AlerteController extends AbstractController 
{
    public function __construct(private AlerteQueryService $alerteQueryService,
                                private AlerteCommandService $alerteCommandService
                                ){}

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

    #[Route('/{id}/lu', methods: ['PATCH'])]
    public function marquerAlerteVue(int $id): JsonResponse
    {
        $admin = $this->getUser();

        if (!$admin instanceof Utilisateur) { throw $this->createAccessDeniedException(); }

        $alerte = $this->alerteCommandService->marquerAlerteVue($id, $admin);

        return $this->json($this->serializeAlerte($alerte));
    }

    
    private function serializeAlerte(Alerte $alerte): array
    {
     $retard = $alerte->getRetard();
     $absence = $alerte->getAbsence();

     $employe = $retard?->getEmploye() ?? $absence?->getEmploye();
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

        'absence' => $absence ? [
            'id' => $absence->getId(),
            'date' => $absence->getDate()?->format('Y-m-d'),
            'statut' => $absence->getStatut()?->value,
            'typeAbsence' => $absence->getTypeAbsence()?->value,
            'periode' => method_exists($absence, 'getLibellePeriode')
                ? $absence->getLibellePeriode()
                : null,
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