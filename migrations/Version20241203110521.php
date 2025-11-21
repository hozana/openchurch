<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Override;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241203110521 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE field_community (field_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', community_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_FE63C6B9443707B0 (field_id), INDEX IDX_FE63C6B9FDA7B0BF (community_id), PRIMARY KEY(field_id, community_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE field_place (field_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', place_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_5FBAC536443707B0 (field_id), INDEX IDX_5FBAC536DA6A219 (place_id), PRIMARY KEY(field_id, place_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE field_community ADD CONSTRAINT FK_FE63C6B9443707B0 FOREIGN KEY (field_id) REFERENCES field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_community ADD CONSTRAINT FK_FE63C6B9FDA7B0BF FOREIGN KEY (community_id) REFERENCES community (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_place ADD CONSTRAINT FK_5FBAC536443707B0 FOREIGN KEY (field_id) REFERENCES field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_place ADD CONSTRAINT FK_5FBAC536DA6A219 FOREIGN KEY (place_id) REFERENCES place (id) ON DELETE CASCADE');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE field_community DROP FOREIGN KEY FK_FE63C6B9443707B0');
        $this->addSql('ALTER TABLE field_community DROP FOREIGN KEY FK_FE63C6B9FDA7B0BF');
        $this->addSql('ALTER TABLE field_place DROP FOREIGN KEY FK_5FBAC536443707B0');
        $this->addSql('ALTER TABLE field_place DROP FOREIGN KEY FK_5FBAC536DA6A219');
        $this->addSql('DROP TABLE field_community');
        $this->addSql('DROP TABLE field_place');
    }
}
