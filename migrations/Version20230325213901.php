<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230325213901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_history_file (classement_history_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_4C20D8BA3D690B97 (classement_history_id), INDEX IDX_4C20D8BA93CB796C (file_id), PRIMARY KEY(classement_history_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement_history_file ADD CONSTRAINT FK_4C20D8BA3D690B97 FOREIGN KEY (classement_history_id) REFERENCES classement_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_file ADD CONSTRAINT FK_4C20D8BA93CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_file_history DROP FOREIGN KEY FK_1647A6053D690B97');
        $this->addSql('ALTER TABLE classement_history_file_history DROP FOREIGN KEY FK_1647A605A7ED5FE4');
        $this->addSql('DROP TABLE file_history');
        $this->addSql('DROP TABLE classement_history_file_history');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_history (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, size INT NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7CDCC970B548B0F (path), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE classement_history_file_history (classement_history_id INT NOT NULL, file_history_id INT NOT NULL, INDEX IDX_1647A6053D690B97 (classement_history_id), INDEX IDX_1647A605A7ED5FE4 (file_history_id), PRIMARY KEY(classement_history_id, file_history_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE classement_history_file_history ADD CONSTRAINT FK_1647A6053D690B97 FOREIGN KEY (classement_history_id) REFERENCES classement_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_file_history ADD CONSTRAINT FK_1647A605A7ED5FE4 FOREIGN KEY (file_history_id) REFERENCES file_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_history_file DROP FOREIGN KEY FK_4C20D8BA3D690B97');
        $this->addSql('ALTER TABLE classement_history_file DROP FOREIGN KEY FK_4C20D8BA93CB796C');
        $this->addSql('DROP TABLE classement_history_file');
    }
}
