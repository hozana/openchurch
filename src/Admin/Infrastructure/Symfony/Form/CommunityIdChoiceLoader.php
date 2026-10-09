<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Symfony\Form;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CommunityIdChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @param list<string> $knownIds
     */
    public function __construct(
        private array $knownIds,
    ) {
    }

    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->knownIds, $value);
    }

    /**
     * Keeps the keys of the given values, as expected by ChoiceType; malformed ids are left out.
     *
     * @param array<mixed> $values
     *
     * @return array<string>
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $choices = [];
        foreach ($values as $key => $id) {
            if (is_string($id) && Uuid::isValid($id)) {
                $choices[$key] = Uuid::fromString($id)->toRfc4122();
            }
        }

        return $choices;
    }

    /**
     * @param array<mixed> $choices
     *
     * @return array<string>
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return array_filter($choices, is_string(...));
    }
}
