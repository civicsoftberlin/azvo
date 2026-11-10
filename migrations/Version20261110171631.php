<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110171631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_untis_teacher ON overtime_entry');
        $this->addSql('CREATE UNIQUE INDEX unique_untis_teacher ON overtime_entry (untis_id, incident_date, teacher_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_untis_teacher ON overtime_entry');
        $this->addSql('CREATE UNIQUE INDEX unique_untis_teacher ON overtime_entry (untis_id, teacher_id)');
    }
}
