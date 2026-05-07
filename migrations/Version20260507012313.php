<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507012313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add classement_vote table for user voting on classements with emoji (👍 and 👎)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_vote (id INT AUTO_INCREMENT NOT NULL, vote_type VARCHAR(10) NOT NULL, date_create DATETIME NOT NULL, user_id INT NOT NULL, classement_id INT NOT NULL, INDEX IDX_4D0D2B41A76ED395 (user_id), INDEX IDX_4D0D2B41A513A63E (classement_id), UNIQUE INDEX unique_user_classement_vote (user_id, classement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE classement_vote ADD CONSTRAINT FK_4D0D2B41A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_vote ADD CONSTRAINT FK_4D0D2B41A513A63E FOREIGN KEY (classement_id) REFERENCES classement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classement_vote DROP FOREIGN KEY FK_4D0D2B41A76ED395');
        $this->addSql('ALTER TABLE classement_vote DROP FOREIGN KEY FK_4D0D2B41A513A63E');
        $this->addSql('DROP TABLE classement_vote');
    }
}
