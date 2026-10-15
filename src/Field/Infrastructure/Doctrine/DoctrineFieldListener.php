<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\Doctrine;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Application\CommunitySearchIndexer;
use App\FieldHolder\Community\Domain\Model\Community;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Reindexes the communities whose searchable fields were created, updated or removed, including
 * new communities.
 *
 * Communities are collected during the flush and indexed in postFlush, all at once. A plain flush has
 * committed its transaction by then, so a failed write never reaches the index; inside an explicit
 * transaction, postFlush runs before the final commit.
 * The index is a copy of the database: when indexing fails, the error is logged and the write is kept
 * (app:index:communities rebuilds the index).
 */
#[AsEntityListener(event: Events::postPersist, method: 'onFieldChange', entity: Field::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onFieldChange', entity: Field::class)]
#[AsEntityListener(event: Events::postRemove, method: 'onFieldChange', entity: Field::class)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DoctrineFieldListener implements ResetInterface
{
    private const array INDEXED_FIELDS = [
        FieldCommunity::TYPE->value,
        FieldCommunity::NAME->value,
        FieldCommunity::PARENT_COMMUNITY_ID->value,
    ];

    /** @var array<int, Community> Keyed by object id to index each community once */
    private array $communitiesToIndex = [];

    public function __construct(
        private readonly string $synchroSecretKey,
        private readonly Security $security,
        private readonly CommunitySearchIndexer $indexer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onFieldChange(Field $field): void
    {
        $user = $this->security->getUser();
        // We do not index during bulk synchronization from wikidata
        if ($user instanceof Agent && $user->apiKey === $this->synchroSecretKey) {
            return;
        }

        // We only index community
        if (null === $field->community || !in_array($field->name, self::INDEXED_FIELDS, true)) {
            return;
        }

        $this->communitiesToIndex[spl_object_id($field->community)] = $field->community;
    }

    public function postFlush(): void
    {
        $communities = $this->communitiesToIndex;
        $this->reset();
        if ([] === $communities) {
            return;
        }

        try {
            $this->indexer->index(...array_values($communities));
        } catch (Throwable $e) {
            // Whatever the failure, the data is already written: reporting an error would wrongly tell it wasn't
            $this->logger->error('Could not index communities {ids}: {message}', [
                'ids' => implode(', ', array_map(static fn (Community $community): string => (string) $community->id?->toString(), $communities)),
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Forgets the communities of a flush that failed, so that they don't leak into the next request
     * of a long-running worker.
     */
    public function reset(): void
    {
        $this->communitiesToIndex = [];
    }
}
