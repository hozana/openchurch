<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20241220160327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO agent (id, name, api_key) VALUES (:id, "CLI_SYNCHRO", :syncroSecretKey)',
            [
                'id' => Uuid::v7()->toBinary(),
                'syncroSecretKey' => $_ENV['SYNCHRO_SECRET_KEY'],
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM agent WHERE name = "CLI_SYNCHRO"');
    }
}
