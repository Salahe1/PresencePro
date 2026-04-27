<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421091637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departement ADD horaire_travail_id INT NOT NULL');
        $this->addSql('ALTER TABLE departement ADD CONSTRAINT FK_C1765B632F055838 FOREIGN KEY (horaire_travail_id) REFERENCES horaire_travail (id)');
        $this->addSql('CREATE INDEX IDX_C1765B632F055838 ON departement (horaire_travail_id)');
        $this->addSql('ALTER TABLE horaire_travail DROP FOREIGN KEY `FK_A74025A8CCF9E01E`');
        $this->addSql('DROP INDEX IDX_A74025A8CCF9E01E ON horaire_travail');
        $this->addSql('ALTER TABLE horaire_travail DROP departement_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departement DROP FOREIGN KEY FK_C1765B632F055838');
        $this->addSql('DROP INDEX IDX_C1765B632F055838 ON departement');
        $this->addSql('ALTER TABLE departement DROP horaire_travail_id');
        $this->addSql('ALTER TABLE horaire_travail ADD departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE horaire_travail ADD CONSTRAINT `FK_A74025A8CCF9E01E` FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE INDEX IDX_A74025A8CCF9E01E ON horaire_travail (departement_id)');
    }
}
