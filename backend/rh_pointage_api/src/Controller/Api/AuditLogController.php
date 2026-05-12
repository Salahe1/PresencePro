<?php

namespace App\Controller\Api;

use App\entity\AuditLog;
use App\Repository\AuditLogRepository;

use Symfony\Bundle\FrameworkBundle\controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/audit-logs')]
class AuditLogController extends AbstractController
{
    public function __construct(
        private AuditLogRepository $auditLogRepository  
    ){}

    #[Route('', methods: ['GET'])]
    public function listeAuditLogs():JsonResponse
    {
        $logs = $this->auditLogsRepository->findBy([],['dateAction'=>'DESC',100]);

        return $this->json(array_map(
            fn (AuditLog $logs) => $this->serializeAuditlog($logs), $logs
        ));
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detailsAudit(int $id): JsonResponse
    {
        $log = $this->auditLogRepository->find($id);

        if(!$log){throw $this->createNotFoundException('Log introuvable');}

        return $this->json($this->serializeAuditLog($log));
    }

    private function serializeAuditLog(AuditLog $log): array
    {
        return [
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'dateAction' => $log->getDateAction()?->format('Y-m-d H:i:s'),
            'entite' => $log->getEntite(),
            'entiteId' => $log->getEntiteId(),
            'ancienneValeur' => $log->getAncienneValeur(),
            'nouvelleValeur' => $log->getNouvelleValeur(),
            'adresseIP' => $log->getAdresseIP(),
            'utilisateur' => $log->getUtilisateur() ? [
                'id' => $log->getUtilisateur()->getId(),
                'nomComplet' => $log->getUtilisateur()->getNomComplet(),
                'email' => $log->getUtilisateur()->getEmail(),
            ] : null,
        ];
    }
}