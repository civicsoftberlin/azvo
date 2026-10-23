<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261011181700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE untis_import ADD upload_name VARCHAR(255) DEFAULT NULL, ADD upload_original_name VARCHAR(255) DEFAULT NULL, ADD upload_mime_type VARCHAR(255) DEFAULT NULL, ADD upload_size INT DEFAULT NULL, ADD upload_dimensions LONGTEXT DEFAULT NULL, CHANGE filename upload_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE untis_import ADD filename VARCHAR(255) DEFAULT NULL, DROP upload_file, DROP upload_name, DROP upload_original_name, DROP upload_mime_type, DROP upload_size, DROP upload_dimensions');
    }
}
