<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Symfony\Command;

use App\Community\Domain\Enum\CommunityIndex;
use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Core\Infrastructure\ElasticSearch\OfficialElasticSearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:elastic:populate')]
class PopulateElasticIndexCommand extends Command
{
    private const BULK_SIZE = 1000;

    public function __construct(
        private OfficialElasticSearchService $elasticService,
        private CommunityRepositoryInterface $communityRepo,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('Deleting %s index...', CommunityIndex::PARISH->value));
        $this->elasticService->deleteIndex(CommunityIndex::PARISH);

        $output->writeln(sprintf('Creating %s index...', CommunityIndex::PARISH->value));
        $this->elasticService->createIndex(CommunityIndex::PARISH);
        $this->elasticService->putMapping(CommunityIndex::PARISH);
        $this->indexParishes($output);

        return COMMAND::SUCCESS;
    }

    private function indexParishes(OutputInterface $output): void
    {
        $i = 1;
        $start_memory = memory_get_usage();
        
        while (true) {
            $output->writeln(sprintf('iteration %s', $i));
            $parishes = $this->communityRepo
                ->withType(CommunityType::PARISH->value)
                ->withPagination($i, self::BULK_SIZE);

            $idsToIndex = [];
            $parishesToIndex = [];

            foreach ($parishes as $parish) {
                $parishName = $parish->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                $idsToIndex[] = $parish->id;
                $parishesToIndex[] = [
                    'parishName' => $parishName,
                    'dioceseName' => null, //TODO : add diocese name
                ];
            }

            $this->elasticService->bulkIndex(
                CommunityIndex::PARISH, $idsToIndex, $parishesToIndex
            );
            $output->writeln("BULKED $i");


            if (count($idsToIndex) < self::BULK_SIZE) {
                break; // we stop the loop once we reach the last bulk
            }

            $parishes->clear();
            $output->writeln(memory_get_usage()." After clear - ".$start_memory);

            $i++;
        }
    }
}