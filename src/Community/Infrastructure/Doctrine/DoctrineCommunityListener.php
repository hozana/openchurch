<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\Doctrine;

use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Model\Community;
use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Shared\Domain\Enum\SearchIndex;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Community::class)]
final class DoctrineCommunityListener
{
    public function __construct(
        private readonly SearchHelperInterface $searchHelper,
    ) {
    }

    public function postPersist(Community $community): void
    {
        $type = $community->getMostTrustableFieldByName(FieldCommunity::TYPE)?->getValue();
        if ($type === CommunityType::PARISH->value) {
            // A new parish has been inserted
            $parishName = $community->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();
            $dioceseName = null;
            /** @var Community|null $diocese */
            $diocese = $community->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID)?->getValue();
            if ($diocese) {
                $dioceseName = $diocese->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();
            }
            $this->searchHelper->upsertElement(
                SearchIndex::PARISH,
                $community->id->toString(),
                [
                    'parishName' => $parishName,
                    'dioceseName' => $dioceseName,
                ]
            );
        }

        if ($type === CommunityType::DIOCESE->value) {
            // A new diocese has been inserted
            $dioceseName = $community->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();
            $this->searchHelper->upsertElement(
                SearchIndex::DIOCESE,
                $community->id->toString(),
                [
                    'dioceseName' => $dioceseName,
                ]
            );
        }
    }
}
