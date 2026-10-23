<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261011171817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE overtime_entry (id INT AUTO_INCREMENT NOT NULL, untis_id VARCHAR(255) DEFAULT NULL, teacher_shortname VARCHAR(255) NOT NULL, incident_date DATE NOT NULL, course_hour INT NOT NULL, amount INT NOT NULL, reason VARCHAR(255) NOT NULL, subject VARCHAR(255) DEFAULT NULL, class VARCHAR(255) DEFAULT NULL, room VARCHAR(255) DEFAULT NULL, expires_at DATE NOT NULL, created_at DATETIME DEFAULT NULL, teacher_id INT DEFAULT NULL, invalidated_by_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8A850511D331BF40 (invalidated_by_id), UNIQUE INDEX unique_untis_teacher (untis_id, teacher_id), INDEX IDX_8A85051141807E1D (teacher_id), INDEX IDX_8A850511B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE overtime_entry ADD CONSTRAINT FK_8A85051141807E1D FOREIGN KEY (teacher_id) REFERENCES teacher (id)');
        $this->addSql('ALTER TABLE overtime_entry ADD CONSTRAINT FK_8A850511D331BF40 FOREIGN KEY (invalidated_by_id) REFERENCES overtime_entry (id)');
        $this->addSql('ALTER TABLE overtime_entry ADD CONSTRAINT FK_8A850511B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overtime_entry DROP FOREIGN KEY FK_8A85051141807E1D');
        $this->addSql('ALTER TABLE overtime_entry DROP FOREIGN KEY FK_8A850511D331BF40');
        $this->addSql('ALTER TABLE overtime_entry DROP FOREIGN KEY FK_8A850511B03A8386');
        $this->addSql('DROP TABLE overtime_entry');
    }
}
