<?php

declare(strict_types=1);

namespace App\Tests\Community\Integration\Doctrine;

use App\Community\Domain\Enum\CommunityType;
use App\Community\Infrastructure\Doctrine\DoctrineCommunityListener;
use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DoctrineCommunityListenerTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private DoctrineCommunityListener $listener;
    public SearchServiceInterface $searchService;
    public SearchHelperInterface $searchHelper;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
    }

    protected function setUp(): void
    {
        $this->listener = static::getContainer()->get(DoctrineCommunityListener::class);
        $this->searchService = static::getContainer()->get(SearchServiceInterface::class);
        $this->searchHelper = static::getContainer()->get(SearchHelperInterface::class);

        $this->searchHelper->deleteIndex(SearchIndex::DIOCESE);
        $this->searchHelper->createIndex(SearchIndex::DIOCESE);
        $this->searchHelper->deleteIndex(SearchIndex::PARISH);
        $this->searchHelper->createIndex(SearchIndex::PARISH);
    }

    public function testPostPersistParish(): void
    {
        $diocese = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Diocèse de Nantes',
                ]),
            ],
        ]);

        $parish = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse du Haillon',
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese->_real(),
                ]),
            ],
        ]);
        $this->searchHelper->refresh(SearchIndex::PARISH);

        $parish = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame($parish['_source']['parishName'], 'Paroisse du Haillon');
        self::assertSame($parish['_source']['dioceseName'], 'Diocèse de Nantes');

        $diocese = $this->searchHelper->getDocument(SearchIndex::DIOCESE, $diocese->id->toString());
        self::assertSame($parish['_source']['dioceseName'], 'Diocèse de Nantes');
    }
}
