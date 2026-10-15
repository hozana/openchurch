<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\Symfony;

use App\FieldHolder\Community\Application\CommunitySearchIndexer;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Infrastructure\ElasticSearch\BulkIndexException;
use App\Shared\Domain\Enum\SearchIndex;
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
        private readonly CommunitySearchIndexer $indexer,
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

        // Candidates are the communities some agent gives the type to: the indexer keeps those whose
        // retained type it is
        $output->writeln('Indexing dioceses...');
        $dioceses = $this->communityRepo->addSelectField()->withType(CommunityType::DIOCESE->value)->asCollection();
        $succeeded = $this->write(SearchIndex::DIOCESE, CommunitySearchIndexer::dioceseDocuments($dioceses), $output);

        $output->writeln('Indexing parishes...');
        $totalCount = $this->communityRepo->addSelectField()->withType(CommunityType::PARISH->value)->count();
        for ($page = 1;; ++$page) {
            $output->writeln(sprintf('iteration %s/%s', $page, ceil($totalCount / self::BULK_SIZE)));
            $parishes = $this->communityRepo
                ->addSelectField()
                ->withType(CommunityType::PARISH->value)
                ->withPagination($page, self::BULK_SIZE)
            ;
            $candidates = iterator_to_array($parishes, false);
            $succeeded = $this->write(SearchIndex::PARISH, $this->indexer->parishDocuments($candidates), $output) && $succeeded;

            if (count($candidates) < self::BULK_SIZE) {
                break; // we stop the loop once we reach the last bulk
            }

            $parishes->clear();
        }

        return $succeeded ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * A rejected batch doesn't stop the indexing: the other ones are still worth indexing.
     *
     * @param array<string, array<string, mixed>> $documents indexed by id
     */
    private function write(SearchIndex $index, array $documents, OutputInterface $output): bool
    {
        try {
            $this->elasticHelper->bulkIndex($index, array_keys($documents), array_values($documents));

            return true;
        } catch (BulkIndexException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return false;
        }
    }
}
