<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Symfony\Command;

use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Model\Community;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Shared\Domain\Enum\SearchIndex;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:write:indexes')]
class WriteIndexesCommand extends Command
{
    private const BULK_SIZE = 1000;

    public function __construct(
        private SearchHelperInterface $elasticHelper,
        private CommunityRepositoryInterface $communityRepo,
    )
    {
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

        $output->writeln(sprintf('Indexing dioceses...'));
        $this->createDioceseIndexes($dioceses);
        $output->writeln(sprintf('Indexing parishes...'));
        $this->createParishIndexes($dioceses, $output);

        return COMMAND::SUCCESS;
    }

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
     * @param Community[]|Collection $dioceses
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
                $dioceseName = null;
                $parishName = $parish->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                $parentId = $parish->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID)?->getValue()?->id?->toString();
                
                if ($parentId) {
                    $parentDiocese = 
                        $dioceses->filter(function (Community $diocese) use ($parentId) {
                            return $diocese->id->toString() === $parentId;
                        })->first();

                    if ($parentDiocese) {
                        $dioceseName = $parentDiocese->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                    }
                }

                $idsToIndex[] = $parish->id;
                $parishesToIndex[] = [
                    'parishName' => $parishName,
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
            $i++;
        }
    }
}