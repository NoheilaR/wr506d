<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add API Key management fields to User table
 */
final class Version20251210143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add API Key management fields (hash, prefix, enabled, created_at, last_used_at) to user table';
    }

    public function up(Schema $schema): void
    {
        // Add API Key fields
        $this->addSql('ALTER TABLE user ADD api_key_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD api_key_prefix VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD api_key_enabled TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD api_key_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user ADD api_key_last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Create unique index on api_key_hash for security
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A9F66D9E ON user (api_key_hash)');

        // Create index on api_key_prefix for optimized lookup
        $this->addSql('CREATE INDEX IDX_8D93D649B285E7F7 ON user (api_key_prefix)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes first
        $this->addSql('DROP INDEX UNIQ_8D93D649A9F66D9E ON user');
        $this->addSql('DROP INDEX IDX_8D93D649B285E7F7 ON user');

        // Drop columns
        $this->addSql('ALTER TABLE user DROP api_key_hash');
        $this->addSql('ALTER TABLE user DROP api_key_prefix');
        $this->addSql('ALTER TABLE user DROP api_key_enabled');
        $this->addSql('ALTER TABLE user DROP api_key_created_at');
        $this->addSql('ALTER TABLE user DROP api_key_last_used_at');
    }
}
