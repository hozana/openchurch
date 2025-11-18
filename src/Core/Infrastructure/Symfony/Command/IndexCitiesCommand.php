<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Symfony\Command;

use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\CitiesDownloaderInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:index:cities')]
class IndexCitiesCommand extends Command
{
    private const BULK_SIZE = 500;

    public function __construct(
        private SearchHelperInterface $elasticHelper,
        private readonly CitiesDownloaderInterface $citiesDownloader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('Deleting %s index...', SearchIndex::CITY->value));
        $this->elasticHelper->deleteIndex(SearchIndex::CITY);

        $output->writeln(sprintf('Creating %s index...', SearchIndex::CITY->value));
        $this->elasticHelper->createIndex(SearchIndex::CITY);
        $this->elasticHelper->putMapping(SearchIndex::CITY);

        $i = 0;
        $cities = $this->citiesDownloader->getCities();

        while (true) {
            $citiesToIndex = [];
            $idsToIndex = [];
            $output->writeln(sprintf('iteration %s/%s', ($i / self::BULK_SIZE), ceil(count($cities) / self::BULK_SIZE)));

            for ($it = $i; $it < min($i + self::BULK_SIZE, count($cities) - 1); $it++) {
                $idsToIndex[] = $cities[$it]['name'];
                $citiesToIndex[] = [
                    'cityName' => $cities[$it]['name'],
                    'zipCode' => $cities[$it]['zipCode'],
                ];
            }

            $this->elasticHelper->bulkIndex(
                SearchIndex::CITY, $idsToIndex, $citiesToIndex
            );

            if ($i >= count($cities)) {
                break;
            }

            $i += self::BULK_SIZE;
        }

        $output->writeln(sprintf('Done. %s cities registered', count($cities)));
        return Command::SUCCESS;
    }

    // private function createCityIndexes(OutputInterface $output): void
    // {
    //     $officialCityData = "https://datanova.laposte.fr/data-fair/api/v1/datasets/laposte-hexasmal/raw"
    // }
}
