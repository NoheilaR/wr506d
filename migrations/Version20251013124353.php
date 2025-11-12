<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013124353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_object (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE actor CHANGE photo_size image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE actor ADD CONSTRAINT FK_447556F93DA5256D FOREIGN KEY (image_id) REFERENCES media_object (id)');
        $this->addSql('CREATE INDEX IDX_447556F93DA5256D ON actor (image_id)');
        $this->addSql('ALTER TABLE movie ADD poster_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE movie ADD CONSTRAINT FK_1D5EF26F5BB66C05 FOREIGN KEY (poster_id) REFERENCES media_object (id)');
        $this->addSql('CREATE INDEX IDX_1D5EF26F5BB66C05 ON movie (poster_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actor DROP FOREIGN KEY FK_447556F93DA5256D');
        $this->addSql('ALTER TABLE movie DROP FOREIGN KEY FK_1D5EF26F5BB66C05');
        $this->addSql('DROP TABLE media_object');
        $this->addSql('DROP INDEX IDX_1D5EF26F5BB66C05 ON movie');
        $this->addSql('ALTER TABLE movie DROP poster_id');
        $this->addSql('DROP INDEX IDX_447556F93DA5256D ON actor');
        $this->addSql('ALTER TABLE actor CHANGE image_id photo_size INT DEFAULT NULL');
    }
}
