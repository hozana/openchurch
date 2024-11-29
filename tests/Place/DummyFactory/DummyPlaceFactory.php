<?php

namespace App\Tests\Place\DummyFactory;

use App\Place\Domain\Model\Place;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Place>
 */
final class DummyPlaceFactory extends PersistentProxyObjectFactory
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
        return Place::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Place $place): void {})
        ;
    }

    public function withField(array $fieldData): self
    {
        return $this->afterInstantiate(function(Place $place) use ($fieldData) {
            $field = DummyFieldFactory::createOne(array_merge(
                $fieldData,
                ['place' => $place]
            ))->_save();

            $place->fields->add($field);

            // $place->addField($field);
        });
    }
}
