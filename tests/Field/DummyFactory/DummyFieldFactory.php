<?php

namespace App\Tests\Field\DummyFactory;

use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Field>
 */
final class DummyFieldFactory extends PersistentObjectFactory
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
        return Field::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'agent' => DummyAgentFactory::new(),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'engine' => FieldEngine::AI,
            'name' => self::faker()->text(),
            'reliability' => FieldReliability::LOW,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Field $field): void {})
        ;
    }
}
