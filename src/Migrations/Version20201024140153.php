<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201024140153 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE places (place_id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name LONGTEXT DEFAULT NULL, country_code LONGTEXT DEFAULT NULL, type ENUM(\'city\', \'country\', \'state\', \'area\', \'unknown\') NOT NULL COMMENT \'(DC2Type:PlaceType)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_FEAF6C55727ACA70 (parent_id), PRIMARY KEY(place_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_C74F21955F37A13B (token), INDEX IDX_C74F219519EB6921 (client_id), INDEX IDX_C74F2195A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE churches (church_id INT AUTO_INCREMENT NOT NULL, wikidata_church_id INT DEFAULT NULL, theodia_church_id INT DEFAULT NULL, masses_url LONGTEXT DEFAULT NULL, INDEX IDX_E533287F959FC021 (wikidata_church_id), INDEX IDX_E533287FBA5F2368 (theodia_church_id), PRIMARY KEY(church_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', fullname VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_8D93D649A0D96FBF (email_canonical), UNIQUE INDEX UNIQ_8D93D649C05FB297 (confirmation_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE access_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_B6A2DD685F37A13B (token), INDEX IDX_B6A2DD6819EB6921 (client_id), INDEX IDX_B6A2DD68A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photos (photo_id INT AUTO_INCREMENT NOT NULL, wikidata_church_id INT DEFAULT NULL, theodia_church_id INT DEFAULT NULL, url LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_876E0D9959FC021 (wikidata_church_id), INDEX IDX_876E0D9BA5F2368 (theodia_church_id), PRIMARY KEY(photo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE auth_code (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5933D02C5F37A13B (token), INDEX IDX_5933D02C19EB6921 (client_id), INDEX IDX_5933D02CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wikidata_churches (wikidata_church_id INT AUTO_INCREMENT NOT NULL, place_id INT NOT NULL, name LONGTEXT DEFAULT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, address LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F72BF489DA6A219 (place_id), PRIMARY KEY(wikidata_church_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE calendars (calendar_id INT AUTO_INCREMENT NOT NULL, church_id INT NOT NULL, calendar_url LONGTEXT NOT NULL, rite ENUM(\'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\', \'8\', \'9\', \'10\', \'11\', \'12\', \'13\', \'14\', \'15\') NOT NULL COMMENT \'(DC2Type:Rite)\', lang LONGTEXT NOT NULL, type ENUM(\'mass\', \'confession\', \'adoration\', \'unknown\') NOT NULL COMMENT \'(DC2Type:CalendarType)\', hozana_user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_84DF820FC1538FD4 (church_id), PRIMARY KEY(calendar_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theodia_churches (theodia_church_id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(theodia_church_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_C7440455A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE places ADD CONSTRAINT FK_FEAF6C55727ACA70 FOREIGN KEY (parent_id) REFERENCES places (place_id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F219519EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE churches ADD CONSTRAINT FK_E533287F959FC021 FOREIGN KEY (wikidata_church_id) REFERENCES wikidata_churches (wikidata_church_id)');
        $this->addSql('ALTER TABLE churches ADD CONSTRAINT FK_E533287FBA5F2368 FOREIGN KEY (theodia_church_id) REFERENCES theodia_churches (theodia_church_id)');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD6819EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9959FC021 FOREIGN KEY (wikidata_church_id) REFERENCES wikidata_churches (wikidata_church_id)');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9BA5F2368 FOREIGN KEY (theodia_church_id) REFERENCES theodia_churches (theodia_church_id)');
        $this->addSql('ALTER TABLE auth_code ADD CONSTRAINT FK_5933D02C19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE auth_code ADD CONSTRAINT FK_5933D02CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wikidata_churches ADD CONSTRAINT FK_F72BF489DA6A219 FOREIGN KEY (place_id) REFERENCES places (place_id)');
        $this->addSql('ALTER TABLE calendars ADD CONSTRAINT FK_84DF820FC1538FD4 FOREIGN KEY (church_id) REFERENCES churches (church_id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE places DROP FOREIGN KEY FK_FEAF6C55727ACA70');
        $this->addSql('ALTER TABLE wikidata_churches DROP FOREIGN KEY FK_F72BF489DA6A219');
        $this->addSql('ALTER TABLE calendars DROP FOREIGN KEY FK_84DF820FC1538FD4');
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        $this->addSql('ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD68A76ED395');
        $this->addSql('ALTER TABLE auth_code DROP FOREIGN KEY FK_5933D02CA76ED395');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455A76ED395');
        $this->addSql('ALTER TABLE churches DROP FOREIGN KEY FK_E533287F959FC021');
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9959FC021');
        $this->addSql('ALTER TABLE churches DROP FOREIGN KEY FK_E533287FBA5F2368');
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9BA5F2368');
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F219519EB6921');
        $this->addSql('ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD6819EB6921');
        $this->addSql('ALTER TABLE auth_code DROP FOREIGN KEY FK_5933D02C19EB6921');
        $this->addSql('DROP TABLE places');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE churches');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE photos');
        $this->addSql('DROP TABLE auth_code');
        $this->addSql('DROP TABLE wikidata_churches');
        $this->addSql('DROP TABLE calendars');
        $this->addSql('DROP TABLE theodia_churches');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE ext_log_entries');
    }
}
