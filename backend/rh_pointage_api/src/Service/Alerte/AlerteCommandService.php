<?php

namespace App\Service\Alerte;

use App\Entity\Alerte;
use App\Entity\Utilisateur;
use App\Enum\StatutAlerte;
use App\Repository\AlerteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AlerteCommandService
{
    public function __construct(
        private AlerteRepository $alerteRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function marquerAlerteVue(int $id, ?Utilisateur $admin = null): Alerte
    {
        $alerte = $this->alerteRepository->find($id);

        if (!$alerte) { throw new NotFoundHttpException('Alerte introuvable.'); }

        if ($admin !== null) {
            $alerte->marquerTraitee($admin);
        } else {
            $alerte->setStatut(StatutAlerte::LU);
        }

        $this->entityManager->flush();

        return $alerte;
    }
}