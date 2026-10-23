<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261018000456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overtime_entry DROP INDEX UNIQ_8A850511D331BF40, ADD INDEX IDX_8A850511D331BF40 (invalidated_by_id)');
        $this->addSql('ALTER TABLE overtime_entry DROP FOREIGN KEY `FK_8A850511D331BF40`');
        $this->addSql('ALTER TABLE overtime_entry ADD CONSTRAINT FK_8A850511D331BF40 FOREIGN KEY (invalidated_by_id) REFERENCES overtime_entry (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overtime_entry DROP INDEX IDX_8A850511D331BF40, ADD UNIQUE INDEX UNIQ_8A850511D331BF40 (invalidated_by_id)');
        $this->addSql('ALTER TABLE overtime_entry DROP FOREIGN KEY FK_8A850511D331BF40');
        $this->addSql('ALTER TABLE overtime_entry ADD CONSTRAINT `FK_8A850511D331BF40` FOREIGN KEY (invalidated_by_id) REFERENCES overtime_entry (id)');
    }
}
