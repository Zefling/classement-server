<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix vote_type collation to utf8mb4_unicode_ci so GROUP BY and comparisons distinguish emojis correctly';
    }

    public function preUp(Schema $schema): void
    {
        // Drop the unique index only if it still exists (may have been removed by a previous migration attempt)
        $indexes = $this->connection->fetchAllAssociative("
            SELECT INDEX_NAME FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'classement_vote'
              AND INDEX_NAME = 'unique_user_classement_vote_type'
            LIMIT 1
        ");

        if (!empty($indexes)) {
            $this->connection->executeStatement('DROP INDEX unique_user_classement_vote_type ON classement_vote');
        }
    }

    public function up(Schema $schema): void
    {
        // Fix the column collation so GROUP BY and comparisons distinguish emojis correctly
        $this->addSql('ALTER TABLE classement_vote MODIFY vote_type VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classement_vote MODIFY vote_type VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_user_classement_vote_type ON classement_vote (user_id, classement_id, vote_type)');
    }
}
