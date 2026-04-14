<?php

namespace App\Command;

use App\Entity\TerminalPointage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-test-terminal',
    description: 'Crée un terminal de pointage de test'
)]
class CreateTestTerminalCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $secretBrut = 'MonSecretTablet123!';// identifiant de se secret : 01KP61YC0TJ09JG548ENTBAKV0

        $terminal = new TerminalPointage();
        $terminal->setLabel('Tablette test accueil');
        $terminal->setSecretHash(password_hash($secretBrut, PASSWORD_BCRYPT));

        $this->entityManager->persist($terminal);
        $this->entityManager->flush();

        $output->writeln('Terminal créé avec succès.');
        $output->writeln('Identifiant : ' . $terminal->getIdentifiantAsString());
        $output->writeln('Secret brut : ' . $secretBrut);

        return Command::SUCCESS;
    }
}