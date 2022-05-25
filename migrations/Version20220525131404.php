<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220525131404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, size INT NOT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', UNIQUE INDEX UNIQ_8C9F3610B548B0F (path), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement ADD template_id VARCHAR(255) NOT NULL, ADD ranking_id VARCHAR(255) NOT NULL, ADD hide TINYINT(1) NOT NULL, ADD deleted TINYINT(1) NOT NULL, ADD banner VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE file');
        $this->addSql('ALTER TABLE classement DROP template_id, DROP ranking_id, DROP hide, DROP deleted, DROP banner');
    }
}
