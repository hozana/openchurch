<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201024154827 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dioceses (diocese_id INT AUTO_INCREMENT NOT NULL, country_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, INDEX IDX_8849E742F92F3E70 (country_id), PRIMARY KEY(diocese_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parishes (parish_id INT AUTO_INCREMENT NOT NULL, diocese_id INT DEFAULT NULL, country_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, messesinfo_id VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, zip_code VARCHAR(255) NOT NULL, INDEX IDX_DFF9A978B600009 (diocese_id), INDEX IDX_DFF9A978F92F3E70 (country_id), PRIMARY KEY(parish_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dioceses ADD CONSTRAINT FK_8849E742F92F3E70 FOREIGN KEY (country_id) REFERENCES places (place_id)');
        $this->addSql('ALTER TABLE parishes ADD CONSTRAINT FK_DFF9A978B600009 FOREIGN KEY (diocese_id) REFERENCES dioceses (diocese_id)');
        $this->addSql('ALTER TABLE parishes ADD CONSTRAINT FK_DFF9A978F92F3E70 FOREIGN KEY (country_id) REFERENCES places (place_id)');
        $this->addSql('ALTER TABLE dioceses ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE parishes ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dioceses RENAME INDEX idx_8849e742f92f3e70 TO IDX_8354A650F92F3E70');
        $this->addSql('ALTER TABLE parishes RENAME INDEX idx_dff9a978b600009 TO IDX_D8B39AD0B600009');
        $this->addSql('ALTER TABLE parishes RENAME INDEX idx_dff9a978f92f3e70 TO IDX_D8B39AD0F92F3E70');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dioceses RENAME INDEX idx_8354a650f92f3e70 TO IDX_8849E742F92F3E70');
        $this->addSql('ALTER TABLE parishes RENAME INDEX idx_d8b39ad0f92f3e70 TO IDX_DFF9A978F92F3E70');
        $this->addSql('ALTER TABLE parishes RENAME INDEX idx_d8b39ad0b600009 TO IDX_DFF9A978B600009');
        $this->addSql('ALTER TABLE parishes DROP FOREIGN KEY FK_DFF9A978B600009');
        $this->addSql('DROP TABLE dioceses');
        $this->addSql('DROP TABLE parishes');
        $this->addSql('ALTER TABLE dioceses DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE parishes DROP created_at, DROP updated_at');
    }
}
