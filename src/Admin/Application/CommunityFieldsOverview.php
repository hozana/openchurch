<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\FieldValueNormalizer;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Model\Community;
use DateTimeInterface;

final readonly class CommunityFieldsOverview
{
    public function __construct(
        private CommunityLabeler $communityLabeler,
    ) {
    }

    /**
     * @return list<FieldOverview>
     */
    public function build(Community $community, Agent $adminAgent): array
    {
        $labels = $this->relatedCommunityLabels($community);

        $overviews = [];
        foreach (AdminCommunityFields::displayed() as $name) {
            $retained = $community->getMostTrustableFieldByName($name);
            $values = [];
            foreach ($community->getFieldsByName($name) as $field) {
                $values[] = new AgentValue(
                    agentName: $field->agent->name,
                    engine: $field->engine,
                    reliability: $field->reliability,
                    value: self::display($field, $labels),
                    source: $field->source,
                    explanation: $field->explanation,
                    updatedAt: $field->updatedAt ?? $field->createdAt,
                    isAdmin: $field->agent->is($adminAgent),
                    isRetained: $field === $retained,
                );
            }
            usort($values, static fn (AgentValue $a, AgentValue $b): int => $b->isRetained <=> $a->isRetained);

            $overviews[] = new FieldOverview(
                name: $name,
                label: AdminCommunityFields::label($name),
                editable: AdminCommunityFields::isEditable($name),
                values: $values,
                adminState: AdminFieldState::of($community->getFieldByNameAndAgent($name, $adminAgent)),
            );
        }

        return $overviews;
    }

    /**
     * Labels of the communities the relation fields point to, loaded at once.
     *
     * @return array<string, string>
     */
    private function relatedCommunityLabels(Community $community): array
    {
        $ids = [];
        foreach ([FieldCommunity::PARENT_COMMUNITY_ID, FieldCommunity::REPLACES] as $name) {
            foreach ($community->getFieldsByName($name) as $field) {
                $ids = [...$ids, ...(array) FieldValueNormalizer::normalize($field->getValue())];
            }
        }

        return $this->communityLabeler->labels(array_values(array_unique(array_filter($ids, is_string(...)))));
    }

    /**
     * @param array<string, string> $labels
     */
    private static function display(Field $field, array $labels): string
    {
        $value = $field->getValue();
        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        $normalized = FieldValueNormalizer::normalize($value);
        if (in_array($field->name, [FieldCommunity::PARENT_COMMUNITY_ID->value, FieldCommunity::REPLACES->value], true)) {
            return implode(', ', array_map(
                static fn (string $id): string => $labels[$id] ?? $id,
                array_filter((array) $normalized, is_string(...)),
            ));
        }

        return is_array($normalized) ? implode(', ', $normalized) : (string) $normalized;
    }
}
