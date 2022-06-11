<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220611084839 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dioceses ADD gcatholic_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE wikidata_churches ADD parish_id INT DEFAULT NULL, ADD diocese_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wikidata_churches ADD CONSTRAINT FK_F72BF4898707B11F FOREIGN KEY (parish_id) REFERENCES parishes (parish_id)');
        $this->addSql('ALTER TABLE wikidata_churches ADD CONSTRAINT FK_F72BF489B600009 FOREIGN KEY (diocese_id) REFERENCES dioceses (diocese_id)');
        $this->addSql('CREATE INDEX IDX_F72BF4898707B11F ON wikidata_churches (parish_id)');
        $this->addSql('CREATE INDEX IDX_F72BF489B600009 ON wikidata_churches (diocese_id)');
        $this->addSql('ALTER TABLE wikidata_churches ADD messesinfo_id VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wikidata_churches DROP messesinfo_id');
        $this->addSql('ALTER TABLE wikidata_churches DROP FOREIGN KEY FK_F72BF4898707B11F');
        $this->addSql('ALTER TABLE wikidata_churches DROP FOREIGN KEY FK_F72BF489B600009');
        $this->addSql('DROP INDEX IDX_F72BF4898707B11F ON wikidata_churches');
        $this->addSql('DROP INDEX IDX_F72BF489B600009 ON wikidata_churches');
        $this->addSql('ALTER TABLE wikidata_churches DROP parish_id, DROP diocese_id');
        $this->addSql('ALTER TABLE dioceses DROP gcatholic_id');
    }
}
