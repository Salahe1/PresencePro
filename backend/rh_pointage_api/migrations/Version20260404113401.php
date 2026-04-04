<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404113401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, statut VARCHAR(255) NOT NULL, type_absence VARCHAR(255) DEFAULT NULL, employe_id INT NOT NULL, INDEX IDX_765AE0C91B65292 (employe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE alerte (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, traite_par_id INT DEFAULT NULL, retard_id INT DEFAULT NULL, absence_id INT DEFAULT NULL, INDEX IDX_3AE753A167FABE8 (traite_par_id), UNIQUE INDEX UNIQ_3AE753AD58EB63C (retard_id), UNIQUE INDEX UNIQ_3AE753A2DFF238F (absence_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(100) NOT NULL, date_action DATETIME NOT NULL, entite VARCHAR(100) NOT NULL, entite_id INT NOT NULL, ancienne_valeur JSON DEFAULT NULL, nouvelle_valeur JSON DEFAULT NULL, adresse_ip VARCHAR(45) DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_F6E1C0F5FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE calendrier_travail (id INT AUTO_INCREMENT NOT NULL, date_jour DATE DEFAULT NULL, type_jour VARCHAR(255) NOT NULL, est_travaille TINYINT NOT NULL, description LONGTEXT DEFAULT NULL, departement_id INT DEFAULT NULL, INDEX IDX_73535E6ACCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE employe (id INT AUTO_INCREMENT NOT NULL, matricule VARCHAR(50) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, telephone VARCHAR(100) NOT NULL, date_embauche DATE NOT NULL, poste VARCHAR(100) NOT NULL, actif TINYINT NOT NULL, photo VARCHAR(255) DEFAULT NULL, biometrique_data JSON DEFAULT NULL, departement_id INT NOT NULL, type_employe VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F804D3B912B2DC9C (matricule), UNIQUE INDEX UNIQ_F804D3B9E7927C74 (email), INDEX IDX_F804D3B9CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE horaire_travail (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) DEFAULT NULL, tolerance_retard TIME NOT NULL, departement_id INT DEFAULT NULL, INDEX IDX_A74025A8CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE justification_absence (id INT AUTO_INCREMENT NOT NULL, motif VARCHAR(255) NOT NULL, commentaire LONGTEXT DEFAULT NULL, justificatif_path VARCHAR(255) DEFAULT NULL, date_justification DATE NOT NULL, absence_id INT NOT NULL, saisie_par_admin_id INT NOT NULL, UNIQUE INDEX UNIQ_AEB54F6A2DFF238F (absence_id), INDEX IDX_AEB54F6A14725B82 (saisie_par_admin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plage_horaire (id INT AUTO_INCREMENT NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, ordre INT NOT NULL, horaire_travail_id INT NOT NULL, INDEX IDX_86EF8A372F055838 (horaire_travail_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE pointage (id INT AUTO_INCREMENT NOT NULL, time_stamp DATETIME NOT NULL, type VARCHAR(255) NOT NULL, employe_id INT NOT NULL, INDEX IDX_7591B201B65292 (employe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE retard (id INT AUTO_INCREMENT NOT NULL, date_jour DATE NOT NULL, heure_prevue TIME NOT NULL, heure_arrivee TIME NOT NULL, justifie TINYINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, pointage_id INT NOT NULL, employe_id INT NOT NULL, UNIQUE INDEX UNIQ_5C64DDBDE58DA11D (pointage_id), INDEX IDX_5C64DDBD1B65292 (employe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE utilisateur (mot_de_passe VARCHAR(255) DEFAULT NULL, role VARCHAR(255) NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C91B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753A167FABE8 FOREIGN KEY (traite_par_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753AD58EB63C FOREIGN KEY (retard_id) REFERENCES retard (id)');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753A2DFF238F FOREIGN KEY (absence_id) REFERENCES absence (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE calendrier_travail ADD CONSTRAINT FK_73535E6ACCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE horaire_travail ADD CONSTRAINT FK_A74025A8CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE justification_absence ADD CONSTRAINT FK_AEB54F6A2DFF238F FOREIGN KEY (absence_id) REFERENCES absence (id)');
        $this->addSql('ALTER TABLE justification_absence ADD CONSTRAINT FK_AEB54F6A14725B82 FOREIGN KEY (saisie_par_admin_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE plage_horaire ADD CONSTRAINT FK_86EF8A372F055838 FOREIGN KEY (horaire_travail_id) REFERENCES horaire_travail (id)');
        $this->addSql('ALTER TABLE pointage ADD CONSTRAINT FK_7591B201B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE retard ADD CONSTRAINT FK_5C64DDBDE58DA11D FOREIGN KEY (pointage_id) REFERENCES pointage (id)');
        $this->addSql('ALTER TABLE retard ADD CONSTRAINT FK_5C64DDBD1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3BF396750 FOREIGN KEY (id) REFERENCES employe (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C91B65292');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753A167FABE8');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753AD58EB63C');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753A2DFF238F');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5FB88E14F');
        $this->addSql('ALTER TABLE calendrier_travail DROP FOREIGN KEY FK_73535E6ACCF9E01E');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9CCF9E01E');
        $this->addSql('ALTER TABLE horaire_travail DROP FOREIGN KEY FK_A74025A8CCF9E01E');
        $this->addSql('ALTER TABLE justification_absence DROP FOREIGN KEY FK_AEB54F6A2DFF238F');
        $this->addSql('ALTER TABLE justification_absence DROP FOREIGN KEY FK_AEB54F6A14725B82');
        $this->addSql('ALTER TABLE plage_horaire DROP FOREIGN KEY FK_86EF8A372F055838');
        $this->addSql('ALTER TABLE pointage DROP FOREIGN KEY FK_7591B201B65292');
        $this->addSql('ALTER TABLE retard DROP FOREIGN KEY FK_5C64DDBDE58DA11D');
        $this->addSql('ALTER TABLE retard DROP FOREIGN KEY FK_5C64DDBD1B65292');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3BF396750');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE alerte');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE calendrier_travail');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE employe');
        $this->addSql('DROP TABLE horaire_travail');
        $this->addSql('DROP TABLE justification_absence');
        $this->addSql('DROP TABLE plage_horaire');
        $this->addSql('DROP TABLE pointage');
        $this->addSql('DROP TABLE retard');
        $this->addSql('DROP TABLE utilisateur');
    }
}
