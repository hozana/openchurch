<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Override;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241203150437 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF54558FDA7B0BF FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF5455851D1B0E8 FOREIGN KEY (community_val_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF5455882D54272 FOREIGN KEY (place_val_id) REFERENCES place (id)');
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF545583414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF54558DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF54558FDA7B0BF');
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF5455851D1B0E8');
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF5455882D54272');
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF545583414710B');
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF54558DA6A219');
    }
}
