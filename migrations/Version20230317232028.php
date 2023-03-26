<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230317232028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_history (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(20) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', date DATETIME DEFAULT NULL, ranking_id VARCHAR(255) NOT NULL, deleted TINYINT(1) NOT NULL, banner VARCHAR(255) NOT NULL, total_items INT NOT NULL, total_groups INT NOT NULL, INDEX rankingId (ranking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classement_history_file_history (classement_history_id INT NOT NULL, file_history_id INT NOT NULL, INDEX IDX_1647A6053D690B97 (classement_history_id), INDEX IDX_1647A605A7ED5FE4 (file_history_id), PRIMARY KEY(classement_history_id, file_history_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file_history (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, size INT NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7CDCC970B548B0F (path), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement_history_file_history ADD CONSTRAINT FK_1647A6053D690B97 FOREIGN KEY (classement_history_id) REFERENCES classement_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_file_history ADD CONSTRAINT FK_1647A605A7ED5FE4 FOREIGN KEY (file_history_id) REFERENCES file_history (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classement_history_file_history DROP FOREIGN KEY FK_1647A6053D690B97');
        $this->addSql('ALTER TABLE classement_history_file_history DROP FOREIGN KEY FK_1647A605A7ED5FE4');
        $this->addSql('DROP TABLE classement_history');
        $this->addSql('DROP TABLE classement_history_file_history');
        $this->addSql('DROP TABLE file_history');
    }
}
