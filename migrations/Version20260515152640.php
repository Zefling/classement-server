<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515152640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create preferences table for user preferences storage';
    }

    public function up(Schema $schema): void
    {
        // Create preferences table
        $this->addSql('CREATE TABLE preferences (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            encrypted_data TEXT NOT NULL,
            date_create DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            date_change DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_E931F1CFA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE preferences ADD CONSTRAINT FK_E931F1CFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop preferences table
        $this->addSql('DROP TABLE preferences');
    }
}
