<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240925200132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, mode VARCHAR(20) DEFAULT \'default\' NOT NULL, data JSON NOT NULL COMMENT \'(DC2Type:json)\', date_create DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_change DATETIME DEFAULT NULL, theme_id VARCHAR(255) NOT NULL, hidden TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, INDEX IDX_9775E708A76ED395 (user_id), UNIQUE INDEX index_id (theme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme_file (theme_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_C658C22D59027487 (theme_id), INDEX IDX_C658C22D93CB796C (file_id), PRIMARY KEY(theme_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme_tag (theme_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_1BD8CBE759027487 (theme_id), INDEX IDX_1BD8CBE7BAD26311 (tag_id), PRIMARY KEY(theme_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E708A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE theme_file ADD CONSTRAINT FK_C658C22D59027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE theme_file ADD CONSTRAINT FK_C658C22D93CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE theme_tag ADD CONSTRAINT FK_1BD8CBE759027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE theme_tag ADD CONSTRAINT FK_1BD8CBE7BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE classement_history CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E708A76ED395');
        $this->addSql('ALTER TABLE theme_file DROP FOREIGN KEY FK_C658C22D59027487');
        $this->addSql('ALTER TABLE theme_file DROP FOREIGN KEY FK_C658C22D93CB796C');
        $this->addSql('ALTER TABLE theme_tag DROP FOREIGN KEY FK_1BD8CBE759027487');
        $this->addSql('ALTER TABLE theme_tag DROP FOREIGN KEY FK_1BD8CBE7BAD26311');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE theme_file');
        $this->addSql('DROP TABLE theme_tag');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE classement_history CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE classement CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
