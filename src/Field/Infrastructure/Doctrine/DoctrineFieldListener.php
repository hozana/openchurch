<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\Doctrine;

use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\Shared\Domain\Enum\SearchIndex;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Field::class)]
final class DoctrineFieldListener
{
    public function __construct(
        private readonly string $synchroSecretKey,
        private readonly Security $security,
        private readonly SearchHelperInterface $searchHelper,
        private readonly CommunityRepositoryInterface $communityRepo,
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
        $typeField = $community->getMostTrustableFieldByName(FieldCommunity::TYPE);

        if ($typeField->getValue() === CommunityType::PARISH->value) {
            // We updated the name of a parish. We need to update the index
            $this->searchHelper->upsertElement(
                SearchIndex::PARISH,
                $community->id->toString(),
                [
                    'parishName' => $field->getValue(),
                ]
            );
        }

        if ($typeField->getValue() === CommunityType::DIOCESE->value) {
            // We updated the name of a diocese. We need to update the index
            $dioceseName = $community->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
            $this->searchHelper->upsertElement(
                SearchIndex::DIOCESE,
                $community->id->toString(),
                [
                    'dioceseName' => $dioceseName,
                ]
            );

            // We updated the name of a diocese. We have to update all parish children
            $parishes = $this->communityRepo->addSelectField()->withParentCommunityId($community->id);
            foreach ($parishes as $parish) {
                $this->searchHelper->upsertElement(
                    SearchIndex::PARISH,
                    $parish->id->toString(),
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
        $typeField = $community->getMostTrustableFieldByName(FieldCommunity::TYPE);
        if ($typeField->getValue() === CommunityType::PARISH->value) {
            // parent of parish have been updated. We need to update the index if the parent is a diocese
            $parent = $this->communityRepo->addSelectField()->ofId($field->getValue()->id);
            $parentTypeField = $parent->getMostTrustableFieldByName(FieldCommunity::TYPE);
            if ($parentTypeField->getValue() === CommunityType::DIOCESE->value) {
                $dioceseName = $parent->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                $this->searchHelper->upsertElement(
                    SearchIndex::PARISH,
                    $community->id->toString(),
                    [
                        'dioceseName' => $dioceseName,
                    ]
                );
            }
        }
    }
}
