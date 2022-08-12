<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220719220751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classement ADD parent_id VARCHAR(255) DEFAULT NULL, CHANGE ranking_id ranking_id VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55EE9D6D20F64684 ON classement (ranking_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_55EE9D6D20F64684 ON classement');
        $this->addSql('ALTER TABLE classement DROP parent_id, CHANGE ranking_id ranking_id VARCHAR(255) DEFAULT NULL');
    }
}
