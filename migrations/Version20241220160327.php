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
        $syncroSecretKey = $_ENV['SYNCHRO_SECRET_KEY'] ?? null;

        if (!$syncroSecretKey) {
            throw new \RuntimeException('The environment variable SYNCHRO_SECRET_KEY is not defined.');
        }

        $this->addSql(
            'INSERT INTO agent (id, name, api_key) VALUES (:id, "CLI_SYNCHRO", :syncroSecretKey)',
            [
                'id' => Uuid::v7()->toBinary(),
                'syncroSecretKey' => $syncroSecretKey,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM agent WHERE name = "CLI_SYNCHRO"');
    }
}
