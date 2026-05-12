<?php

namespace App\Service\Audit;

use App\Entity\AuditLog;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLoggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {}

    public function log( string $action, string $entite, int $entiteId, ?Utilisateur $utilisateur = null, ?array $ancienneValeur = null, ?array $nouvelleValeur = null, ): AuditLog
    {
        $request = $this->requestStack->getCurrentRequest();

        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setEntite($entite);
        $auditLog->setEntiteId($entiteId);
        $auditLog->setUtilisateur($utilisateur);
        $auditLog->setAdresseIP($request?->getClientIp());
        $auditLog->setDateAction(new \DateTimeImmutable());

        $auditLog->setAncienneValeur($this->sanitize($ancienneValeur));
        $auditLog->setNouvelleValeur($this->sanitize($nouvelleValeur));

        $this->entityManager->persist($auditLog);

        return $auditLog;
    }

    private function sanitize(?array $data): ?array
    {
        if ($data === null) { return null; }

        $sensitiveKeys = [ 'password', 'plainPassword', 'motDePasse',
                           'biometriqueData', 'averageDescriptor', 'descriptors', ];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) { $data[$key] = $this->sanitize($value); }
        }

        return $data;
    }
}