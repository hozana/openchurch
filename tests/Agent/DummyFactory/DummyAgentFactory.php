<?php

namespace App\Tests\Agent\DummyFactory;

use App\Agent\Domain\Model\Agent;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Agent>
 */
final class DummyAgentFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Agent::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'apiKey' => self::faker()->uuid(),
            'name' => self::faker()->name(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Agent $agent): void {})
        ;
    }
}
