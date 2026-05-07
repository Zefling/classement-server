<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507020150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update classement_vote table to allow multiple vote types per user (one row per emoji)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_user_classement_vote ON classement_vote');
        $this->addSql('ALTER TABLE classement_vote CHANGE vote_type vote_type VARCHAR(10) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_user_classement_vote_type ON classement_vote (user_id, classement_id, vote_type)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_user_classement_vote_type ON classement_vote');
        $this->addSql('ALTER TABLE classement_vote CHANGE vote_type vote_type VARCHAR(10) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
        $this->addSql('CREATE UNIQUE INDEX unique_user_classement_vote ON classement_vote (user_id, classement_id)');
    }
}
