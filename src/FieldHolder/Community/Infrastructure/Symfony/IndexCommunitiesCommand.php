<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\Symfony;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:index:communities')]
class IndexCommunitiesCommand extends Command
{
    private const int BULK_SIZE = 100;

    public function __construct(
        private readonly SearchHelperInterface $elasticHelper,
        private readonly CommunityRepositoryInterface $communityRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('Deleting %s, %s index...', SearchIndex::PARISH->value, SearchIndex::DIOCESE->value));
        $this->elasticHelper->deleteIndex(SearchIndex::PARISH);
        $this->elasticHelper->deleteIndex(SearchIndex::DIOCESE);

        $output->writeln(sprintf('Creating %s, %s index...', SearchIndex::PARISH->value, SearchIndex::DIOCESE->value));
        $this->elasticHelper->createIndex(SearchIndex::PARISH);
        $this->elasticHelper->createIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->putMapping(SearchIndex::PARISH);
        $this->elasticHelper->putMapping(SearchIndex::DIOCESE);

        // We get all dioceses
        $dioceses = $this->communityRepo
            ->addSelectField()
            ->withType(CommunityType::DIOCESE->value)
            ->asCollection();

        $output->writeln('Indexing dioceses...');
        $this->createDioceseIndexes($dioceses);
        $output->writeln('Indexing parishes...');
        $this->createParishIndexes($dioceses, $output);

        return Command::SUCCESS;
    }

    /**
     * @param Collection<int, Community> $dioceses
     */
    private function createDioceseIndexes(Collection $dioceses): void
    {
        $idsToIndex = [];
        $diocesesToIndex = [];
        foreach ($dioceses as $diocese) {
            $idsToIndex[] = $diocese->id->toString();
            $dioceseName = $diocese->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();

            $diocesesToIndex[] = [
                'dioceseName' => $dioceseName,
            ];
        }

        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE, $idsToIndex, $diocesesToIndex
        );
    }

    /**
     * @param Collection<int, Community> $dioceses
     */
    private function createParishIndexes(Collection $dioceses, OutputInterface $output): void
    {
        $i = 1;
        $totalCount = $this->communityRepo->addSelectField()->withType(CommunityType::PARISH->value)->count();

        while (true) {
            $output->writeln(sprintf('iteration %s/%s', $i, ceil($totalCount / self::BULK_SIZE)));
            $parishes = $this->communityRepo
                ->addSelectField()
                ->withType(CommunityType::PARISH->value)
                ->withPagination($i, self::BULK_SIZE);

            $idsToIndex = [];
            $parishesToIndex = [];

            foreach ($parishes as $parish) {
                $dioceseName = $dioceseId = null;
                $parishName = $parish->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                $parentId = $parish->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID)?->getValue()?->id?->toString();

                if ($parentId) {
                    $parentDiocese =
                        $dioceses->filter(fn(Community $diocese) => $diocese->id->toString() === $parentId)->first();

                    if ($parentDiocese) {
                        $dioceseId = $parentDiocese->id->toString();
                        $dioceseName = $parentDiocese->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                    }
                }

                $idsToIndex[] = $parish->id;
                $parishesToIndex[] = [
                    'parishName' => $parishName,
                    'dioceseId' => $dioceseId,
                    'dioceseName' => $dioceseName,
                ];
            }

            $this->elasticHelper->bulkIndex(
                SearchIndex::PARISH, $idsToIndex, $parishesToIndex
            );

            if (count($idsToIndex) < self::BULK_SIZE) {
                break; // we stop the loop once we reach the last bulk
            }

            $parishes->clear();
            ++$i;
        }
    }
}
