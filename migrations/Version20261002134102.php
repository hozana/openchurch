<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;
use Symfony\Component\Uid\Uuid;

final class Version20261002134102 extends AbstractMigration
{
    /** @see \App\Admin\Domain\Model\AdminUser::AGENT_NAME */
    private const string ADMIN_AGENT_NAME = 'OPENCHURCH_ADMIN';

    #[Override]
    public function getDescription(): string
    {
        return 'Admin backend: admin users, and the agent they share';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE admin_user (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', agent_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_AD8A54A9E7927C74 (email), INDEX IDX_AD8A54A93414710B (agent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE admin_user ADD CONSTRAINT FK_AD8A54A93414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');

        // The agent shared by all admin users. Nobody authenticates as this agent: its API key only has to
        // be unique and unguessable.
        $this->addSql(
            'INSERT INTO agent (id, name, api_key) SELECT :id, :name, :apiKey FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM agent WHERE name = :name)',
            [
                'id' => Uuid::v7()->toBinary(),
                'name' => self::ADMIN_AGENT_NAME,
                'apiKey' => bin2hex(random_bytes(32)),
            ],
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user DROP FOREIGN KEY FK_AD8A54A93414710B');
        $this->addSql('DROP TABLE admin_user');
        // The values the admin agent wrote are community data: keep the agent if there are any
        $this->addSql(
            'DELETE FROM agent WHERE name = :name AND NOT EXISTS (SELECT 1 FROM field WHERE field.agent_id = agent.id)',
            ['name' => self::ADMIN_AGENT_NAME],
        );
    }
}
