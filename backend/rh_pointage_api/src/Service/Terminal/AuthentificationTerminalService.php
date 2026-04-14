<?php

namespace App\Service\Terminal;

use App\Entity\TerminalPointage;
use App\Repository\TerminalPointageRepository;
use Symfony\Component\Uid\Ulid;


class AuthentificationTerminalService
{
     public function __construct(private TerminalPointageRepository $terminalRepository) {}

    public function authentifier(Ulid $identifiant, string $secret): ?TerminalPointage
    {
        $terminalPointage = $this->terminalRepository->findOneByIdentifiant($identifiant);

        if(!$terminalPointage){ return null;  }

        if(!$terminalPointage->isActif()){ return null;}

        if (!password_verify($secret, $terminalPointage->getSecretHash())) {
           return null;
        }

        return $terminalPointage;
    }
}
        // Logique d'authentification du terminal
        // Par exemple, vérifier l'identifiant et le secret dans la base de données
        // et retourner le TerminalPointage correspondant si l'authentification est réussie.
        
        // Exemple de pseudo-code :
        /*
        $terminal = $this->terminalRepository->findOneByIdentifiant($identifiant);
        if ($terminal && password_verify($secret, $terminal->getSecretHash())) {
            return $terminal;
        }
        return null;
        */
