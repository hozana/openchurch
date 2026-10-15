<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Agent\Domain\Model\Agent;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\FieldValueNormalizer;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\FieldHolder\Community\Domain\Enum\CommunityDeletionReason;
use App\FieldHolder\Community\Domain\Enum\CommunityState;
use App\FieldHolder\Community\Domain\Model\Community;

/**
 * Keeps the parishes an admin parish replaces deleted: designating a parish as replaced deletes it as
 * dissolved, and releasing it brings it back.
 */
final readonly class ParishReplacements
{
    /**
     * Source of the values written on the replaced parishes. It tells them from the ones admins give by hand,
     * so that only the former are removed when the replacement is cancelled.
     */
    public const string SOURCE = 'admin-replacement';

    public function __construct(
        private FieldService $fieldService,
        private FieldRepositoryInterface $fieldRepo,
        private CommunityLabeler $communityLabeler,
    ) {
    }

    /**
     * Must be called before the inputs are applied, since it compares them with the stored values.
     *
     * @param list<AdminFieldInput> $inputs
     */
    public function plan(Community $parish, Agent $agent, array $inputs): ParishReplacementPlan
    {
        foreach ($inputs as $input) {
            if (FieldCommunity::REPLACES !== $input->name) {
                continue;
            }

            $current = array_values(array_filter(
                (array) FieldValueNormalizer::normalize($parish->getFieldByNameAndAgent(FieldCommunity::REPLACES, $agent)?->getValue()),
                is_string(...),
            ));
            $requested = $input->relatedIds();

            return new ParishReplacementPlan(
                array_values(array_diff($requested, $current)),
                array_values(array_diff($current, $requested)),
            );
        }

        return new ParishReplacementPlan([], []);
    }

    /**
     * Only parishes still alive can be designated: the ones that are already linked are deleted by their replacement.
     *
     * @return list<string>
     */
    public function errors(ParishReplacementPlan $plan): array
    {
        $errors = [];
        foreach ($this->communityLabeler->load($plan->designated) as $community) {
            if (self::isDeleted($community)) {
                $errors[] = sprintf('« %s » : « %s » est déjà supprimée.', AdminCommunityFields::label(FieldCommunity::REPLACES), CommunityLabeler::label($community));
            }
        }

        return $errors;
    }

    /**
     * Writes the designated parishes as dissolved, and removes what a former replacement wrote on the released ones.
     * To call once the inputs are applied, so that the name of the replacing parish is the new one.
     *
     * @throws CommunityFieldsRejectedException
     */
    public function apply(Community $parish, Agent $agent, ParishReplacementPlan $plan): void
    {
        if ([] !== $plan->designated && self::isDeleted($parish)) {
            throw new CommunityFieldsRejectedException([sprintf('« %s » : une paroisse supprimée ne peut pas en remplacer d\'autres.', AdminCommunityFields::label(FieldCommunity::REPLACES))]);
        }

        foreach ($this->communityLabeler->load([...$plan->designated, ...$plan->released]) as $replaced) {
            if (in_array($replaced->id?->toRfc4122(), $plan->designated, true)) {
                $this->dissolve($replaced, $parish, $agent);
            } else {
                $this->restore($replaced, $agent);
            }
        }
    }

    private function dissolve(Community $replaced, Community $replacing, Agent $agent): void
    {
        $this->fieldService->upsertFields($replaced, [
            new AdminFieldInput(FieldCommunity::STATE, CommunityState::DELETED->value)->toPayload(self::SOURCE),
            new AdminFieldInput(
                FieldCommunity::DELETION_REASON,
                CommunityDeletionReason::DISSOLVED->value,
                sprintf('Remplacée par %s', CommunityLabeler::label($replacing)),
            )->toPayload(self::SOURCE),
        ], $agent);
    }

    /**
     * Leaves the parish alone when an admin changed its state by hand since: that value is not ours to remove.
     */
    private function restore(Community $replaced, Agent $agent): void
    {
        $state = $replaced->getFieldByNameAndAgent(FieldCommunity::STATE, $agent);
        if (self::SOURCE !== $state?->source) {
            return;
        }

        $this->remove($replaced, $state);
        $reason = $replaced->getFieldByNameAndAgent(FieldCommunity::DELETION_REASON, $agent);
        if (self::SOURCE === $reason?->source) {
            $this->remove($replaced, $reason);
        }
    }

    private function remove(Community $replaced, Field $field): void
    {
        // Leave $field->community set: the search index listener needs it to reindex the parish
        $replaced->fields->removeElement($field);
        $this->fieldRepo->remove($field);
    }

    private static function isDeleted(Community $community): bool
    {
        return CommunityState::DELETED->value === $community->getMostTrustableFieldByName(FieldCommunity::STATE)?->getValue();
    }
}
