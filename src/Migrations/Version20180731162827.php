<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180731162827 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE church ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('UPDATE church SET latitude=(SELECT latitude FROM geo_coordinates WHERE id=geo_id), longitude=(SELECT longitude FROM geo_coordinates WHERE id=geo_id)');

        $this->addSql('ALTER TABLE church DROP FOREIGN KEY FK_90CDDD45FA49D0B');
        $this->addSql('DROP TABLE geo_coordinates');
        $this->addSql('DROP INDEX UNIQ_90CDDD45FA49D0B ON church');
        $this->addSql('ALTER TABLE church DROP geo_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE geo_coordinates (id INT AUTO_INCREMENT NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE church ADD geo_id INT DEFAULT NULL, DROP latitude, DROP longitude');
        $this->addSql('ALTER TABLE church ADD CONSTRAINT FK_90CDDD45FA49D0B FOREIGN KEY (geo_id) REFERENCES geo_coordinates (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90CDDD45FA49D0B ON church (geo_id)');
    }
}
