<?php

namespace App\Service\Terminal;

use App\Entity\TerminalPointage;
use App\Repository\TerminalPointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

class AuthentificationTerminalService
{
    public function __construct(
        private TerminalPointageRepository $terminalRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function authentifier(Ulid $identifiant, string $secret): ?TerminalPointage
    {
        $terminalPointage = $this->terminalRepository->findOneByIdentifiant($identifiant);

        if (!$terminalPointage) {  return null; }

        if (!$terminalPointage->isActif()) { return null; }

        if (!password_verify($secret, $terminalPointage->getSecretHash())) { return null; }

        $now = new \DateTimeImmutable();
        $terminalPointage->setDernierAccesAt($now);
        $terminalPointage->setUpdatedAt($now);

        $this->entityManager->flush();

        return $terminalPointage;
    }
}
