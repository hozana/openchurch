<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\Doctrine;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Field::class)]
final readonly class DoctrineFieldListener
{
    public function __construct(
        private string $synchroSecretKey,
        private Security $security,
        private SearchHelperInterface $searchHelper,
        private CommunityRepositoryInterface $communityRepo,
    ) {
    }

    public function postUpdate(Field $field): void
    {
        /** @var Agent $agent */
        $agent = $this->security->getUser();
        if ($agent && $agent->apiKey === $this->synchroSecretKey) {
            return;
        }

        if ($field->name === FieldCommunity::NAME->value) {
            $this->onFieldNameChange($field);
        }
        if ($field->name === FieldCommunity::PARENT_COMMUNITY_ID->value) {
            $this->onFieldParentCommunityChange($field);
        }
    }

    private function onFieldNameChange(Field $field): void
    {
        $community = $field->community;
        if (null === $community || null === $communityId = $community->id) {
            return;
        }

        $typeField = $community->getMostTrustableFieldByName(FieldCommunity::TYPE);
        if (null === $typeField) {
            return;
        }

        if ($typeField->getValue() === CommunityType::PARISH->value) {
            // We updated the name of a parish. We need to update the index
            $this->searchHelper->upsertElement(
                SearchIndex::PARISH,
                $communityId->toString(),
                [
                    'parishName' => $field->getValue(),
                ]
            );
        }

        if ($typeField->getValue() === CommunityType::DIOCESE->value) {
            // We updated the name of a diocese. We need to update the index
            $dioceseName = $community->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();
            $this->searchHelper->upsertElement(
                SearchIndex::DIOCESE,
                $communityId->toString(),
                [
                    'dioceseName' => $dioceseName,
                ]
            );

            // We updated the name of a diocese. We have to update all parish children
            $parishes = $this->communityRepo->addSelectField()->withParentCommunityId($communityId);
            foreach ($parishes as $parish) {
                if (null === $parishId = $parish->id) {
                    continue;
                }

                $this->searchHelper->upsertElement(
                    SearchIndex::PARISH,
                    $parishId->toString(),
                    [
                        'dioceseName' => $dioceseName,
                    ]
                );
            }
        }
    }

    private function onFieldParentCommunityChange(Field $field): void
    {
        $community = $field->community;
        if (null === $community || null === $communityId = $community->id) {
            return;
        }

        $typeField = $community->getMostTrustableFieldByName(FieldCommunity::TYPE);
        if (null === $typeField || $typeField->getValue() !== CommunityType::PARISH->value) {
            return;
        }

        // parent of parish have been updated. We need to update the index if the parent is a diocese
        $newParent = $field->getValue();
        if (!$newParent instanceof Community || null === $newParent->id) {
            return;
        }

        $parent = $this->communityRepo->addSelectField()->ofId($newParent->id);
        if (null === $parent) {
            return;
        }

        $parentTypeField = $parent->getMostTrustableFieldByName(FieldCommunity::TYPE);
        if (null === $parentTypeField || $parentTypeField->getValue() !== CommunityType::DIOCESE->value) {
            return;
        }

        $dioceseName = $parent->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();
        $this->searchHelper->upsertElement(
            SearchIndex::PARISH,
            $communityId->toString(),
            [
                'dioceseName' => $dioceseName,
            ]
        );
    }
}
