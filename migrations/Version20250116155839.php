<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Override;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250116155839 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE field CHANGE datetime_val datetime_val DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE date_val date_val DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'");
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE field CHANGE datetime_val datetime_val DATETIME DEFAULT NULL, CHANGE date_val date_val DATE DEFAULT NULL');
    }
}
