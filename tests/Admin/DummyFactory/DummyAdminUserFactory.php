<?php

declare(strict_types=1);

namespace App\Tests\Admin\DummyFactory;

use App\Admin\Domain\Model\AdminUser;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<AdminUser>
 */
final class DummyAdminUserFactory extends PersistentObjectFactory
{
    public const string PASSWORD = 'correct horse battery staple';

    /** bcrypt hash of PASSWORD, computed once to keep the tests fast */
    private const string PASSWORD_HASH = '$2y$04$hknhQOK33NO0WIlv5zwZR.zH/Rq6ttSrWGEi3nSN98syGv/RMTu0W';

    public function __construct()
    {
    }

    public static function class(): string
    {
        return AdminUser::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'password' => self::PASSWORD_HASH,
            // Created by the migrations
            'agent' => DummyAgentFactory::findOrCreate(['name' => AdminUser::AGENT_NAME]),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
