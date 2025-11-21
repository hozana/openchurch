<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\Symfony;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\CityLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-parish-zipcode',
    description: 'Fix french parish zipcode using official government data downloadable at https://www.data.gouv.fr/fr/datasets/communes-de-france-base-des-codes-postaux/. \nEg: https://datanova.laposte.fr/data-fair/api/v1/datasets/laposte-hexasmal/raw'
)]
class FixParishZipCodeCommand extends Command
{
    private const int BULK_SIZE = 100;
    /** @var list<array{name: string, wikidataId: int}> */
    private array $invalidZipCodeParishes = [];

    public function __construct(
        private readonly CityLoaderInterface $cityLoader,
        private readonly CommunityRepositoryInterface $communityRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('csvFilePath', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $i = 1;
        $io = new SymfonyStyle($input, $output);
        $progress = $io->createProgressBar($this->communityRepository->count());
        $csvFilePath = $input->getArgument('csvFilePath');
        $cities = $this->cityLoader->getCities($csvFilePath);

        while (true) {
            $parishes = $this->communityRepository
                ->withType(CommunityType::PARISH->value)
                ->withPagination($i, self::BULK_SIZE);

            foreach ($parishes as $parish) {
                $parishName = $parish->getMostTrustableFieldByName(FieldCommunity::NAME)->getValue();
                $zipCode = $parish->getMostTrustableFieldByName(FieldCommunity::CONTACT_ZIPCODE);
                $wikidataId = $parish->getMostTrustableFieldByName(FieldCommunity::WIKIDATA_ID)->getValue();
                
                if ($zipCode === null) {
                    $this->onError($output, sprintf('%s with wikidata %s: has no zip code field', $parishName, $wikidataId), $parishName, $wikidataId);
                    continue;
                }
                $city = array_find($cities, fn ($city) => $city['zipCode'] === $zipCode->getValue());

                if ($city === null) {
                    // No city found with this zip code. It means the zip code is invalid or the zipCode is an inseeCode
                    $city = array_find($cities, fn ($city) => $city['inseeCode'] === $zipCode->getValue());
                    if ($city) {
                        $output->writeln(sprintf('%s with wikidata %s: zip code "%s" is an INSEE code. We fix it', $parishName, $wikidataId, $zipCode->getValue()));
                        $zipCode->value = $city['zipCode'];
                        $zipCode->applyValue();
                        $this->entityManager->flush();
                    }
                    else {
                        $this->onError($output, sprintf('%s with wikidata %s: zip code "%s" is invalid', $parishName, $wikidataId, $zipCode->getValue()), $parishName, $wikidataId);
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            if ($parishes->count() === 0) {
                break;
            }

            ++$i;
            $progress->advance(self::BULK_SIZE);
        }

        $progress->finish();

        $output->writeln('Parishes with invalid zip codes:');
        $output->writeln(print_r($this->invalidZipCodeParishes, true));

        return Command::SUCCESS;
    }

    private function onError(OutputInterface $output, string $message, string $parishName, int $wikidataId): void
    {
        $output->writeln($message);
        $this->invalidZipCodeParishes["name"][] = $parishName;
        $this->invalidZipCodeParishes["wikidataId"][] = $wikidataId;
    }
}
