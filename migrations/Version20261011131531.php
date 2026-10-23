<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261011131531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE teacher_debutat (id INT AUTO_INCREMENT NOT NULL, debutat INT NOT NULL, discount INT DEFAULT NULL, valid_from DATE NOT NULL, invalid_from DATE DEFAULT NULL, teacher_id INT DEFAULT NULL, INDEX IDX_B00BFFEC41807E1D (teacher_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE teacher_debutat ADD CONSTRAINT FK_B00BFFEC41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher_debutat DROP FOREIGN KEY FK_B00BFFEC41807E1D');
        $this->addSql('DROP TABLE teacher_debutat');
    }
}
