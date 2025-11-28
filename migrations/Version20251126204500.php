<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add poster_id column to movie table for VichUploader integration
 */
final class Version20251126204500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add poster_id foreign key to movie table for MediaObject integration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movie ADD poster_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE movie ADD CONSTRAINT FK_1D5EF26F5BB66C05 FOREIGN KEY (poster_id) REFERENCES media_object (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1D5EF26F5BB66C05 ON movie (poster_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movie DROP FOREIGN KEY FK_1D5EF26F5BB66C05');
        $this->addSql('DROP INDEX IDX_1D5EF26F5BB66C05 ON movie');
        $this->addSql('ALTER TABLE movie DROP poster_id');
    }
}
