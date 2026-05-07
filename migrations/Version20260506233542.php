<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506233542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add classement_stats table to track view counts for rankings';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_stats (id INT AUTO_INCREMENT NOT NULL, ranking_id VARCHAR(255) NOT NULL, view_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1C54AE4320F64684 (ranking_id), INDEX ranking_id_idx (ranking_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE classement CHANGE data data JSON NOT NULL, CHANGE date_create date_create DATETIME NOT NULL');
        $this->addSql('ALTER TABLE classement_history CHANGE data data JSON NOT NULL');
        $this->addSql('ALTER TABLE file CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE theme CHANGE data data JSON NOT NULL, CHANGE date_create date_create DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE date_create date_create DATETIME DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE classement_stats');
        $this->addSql('ALTER TABLE classement CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE date_create date_create DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE classement_history CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE file CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE theme CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE date_create date_create DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE date_create date_create DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
