<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030162358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE backup_bag (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE backup_item (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, substitution_id INT NOT NULL, incident_date DATE NOT NULL, hour INT DEFAULT NULL, absence_id INT NOT NULL, class_id INT NOT NULL, absent_teacher VARCHAR(255) DEFAULT NULL, substitution_teacher VARCHAR(255) DEFAULT NULL, absent_course VARCHAR(255) DEFAULT NULL, substitution_course VARCHAR(255) DEFAULT NULL, absent_course_statistics VARCHAR(255) DEFAULT NULL, substitution_course_statistics VARCHAR(255) DEFAULT NULL, absent_room VARCHAR(255) DEFAULT NULL, substitution_room VARCHAR(255) DEFAULT NULL, statistics VARCHAR(255) DEFAULT NULL, absent_classes VARCHAR(255) DEFAULT NULL, absence_reason VARCHAR(255) DEFAULT NULL, absence_comment VARCHAR(255) DEFAULT NULL, bit INT DEFAULT NULL, substitution_classes VARCHAR(255) DEFAULT NULL, substitution_type VARCHAR(1) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, absence_count VARCHAR(5) DEFAULT NULL, backup_bag_id INT DEFAULT NULL, INDEX IDX_841BA0822F7BAB2E (backup_bag_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE backup_item ADD CONSTRAINT FK_841BA0822F7BAB2E FOREIGN KEY (backup_bag_id) REFERENCES backup_bag (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE backup_item DROP FOREIGN KEY FK_841BA0822F7BAB2E');
        $this->addSql('DROP TABLE backup_bag');
        $this->addSql('DROP TABLE backup_item');
    }
}
