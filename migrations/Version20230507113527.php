<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230507113527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_tag (classement_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_A999D11DA513A63E (classement_id), INDEX IDX_A999D11DBAD26311 (tag_id), PRIMARY KEY(classement_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classement_history_tag (classement_history_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_60BB0FC3D690B97 (classement_history_id), INDEX IDX_60BB0FCBAD26311 (tag_id), PRIMARY KEY(classement_history_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_389B783EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement_tag ADD CONSTRAINT FK_A999D11DA513A63E FOREIGN KEY (classement_id) REFERENCES classement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_tag ADD CONSTRAINT FK_A999D11DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_tag ADD CONSTRAINT FK_60BB0FC3D690B97 FOREIGN KEY (classement_history_id) REFERENCES classement_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_tag ADD CONSTRAINT FK_60BB0FCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classement_tag DROP FOREIGN KEY FK_A999D11DA513A63E');
        $this->addSql('ALTER TABLE classement_tag DROP FOREIGN KEY FK_A999D11DBAD26311');
        $this->addSql('ALTER TABLE classement_history_tag DROP FOREIGN KEY FK_60BB0FC3D690B97');
        $this->addSql('ALTER TABLE classement_history_tag DROP FOREIGN KEY FK_60BB0FCBAD26311');
        $this->addSql('DROP TABLE classement_tag');
        $this->addSql('DROP TABLE classement_history_tag');
        $this->addSql('DROP TABLE tag');
    }
}
