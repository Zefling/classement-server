<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508092405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classement_stats_daily table for daily view statistics';
    }

    public function up(Schema $schema): void
    {
        // Create classement_stats_daily table
        $this->addSql('CREATE TABLE classement_stats_daily (
            id INT AUTO_INCREMENT NOT NULL,
            ranking_id VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            view_count INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX ranking_date_idx (ranking_id, date),
            INDEX date_idx (date),
            UNIQUE INDEX unique_ranking_date (ranking_id, date),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Drop classement_stats_daily table
        $this->addSql('DROP TABLE classement_stats_daily');
    }
}
