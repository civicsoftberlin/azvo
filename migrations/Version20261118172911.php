<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261118172911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE daily_report (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, amount INT NOT NULL, type_id INT DEFAULT NULL, INDEX IDX_45114028C54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE daily_report_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE daily_report ADD CONSTRAINT FK_45114028C54C8C93 FOREIGN KEY (type_id) REFERENCES daily_report_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_report DROP FOREIGN KEY FK_45114028C54C8C93');
        $this->addSql('DROP TABLE daily_report');
        $this->addSql('DROP TABLE daily_report_type');
    }
}
