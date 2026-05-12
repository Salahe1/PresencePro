<?php

namespace App\Service\Alerte;

use App\Entity\Alerte;
use App\Entity\Utilisateur;
use App\Enum\StatutAlerte;
use App\Repository\AlerteRepository;
use App\Service\Audit\AuditLoggerService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AlerteCommandService
{
    public function __construct(
        private AlerteRepository $alerteRepository,
        private EntityManagerInterface $entityManager,
        private AuditLoggerService $auditLoggerService,
    ) {
    }

    public function marquerAlerteVue(int $id, ?Utilisateur $admin = null): Alerte
    {
        $alerte = $this->alerteRepository->find($id);

        if (!$alerte) { throw new NotFoundHttpException('Alerte introuvable.'); }
        
        $ancienneValeur = [
            'statut' => $alerte ->getStatut()?->value,
            'traitePar'=> $alerte->getTraitePar()?->getId(),        
        ];

        if ($admin !== null) {
            $alerte->marquerTraitee($admin);
        } else {
            $alerte->setStatut(StatutAlerte::LU);
        }

        $nouvelleValeur = [
            'statut' => $alerte ->getStatut()?->value,
            'traitePar' => $alerte ->getTraitePar()?->getId(),
        ];

        $this->auditLoggerService->log(action: 'ALERTE_MARQUE_VUE',entite: 'Alerte', entiteId: $alerte->getId()
                                       ,utilisateur: $admin ,ancienneValeur: $ancienneValeur ,nouvelleValeur: $nouvelleValeur);

        $this->entityManager->flush();

        return $alerte;
    }
}