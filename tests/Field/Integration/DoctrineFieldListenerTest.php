<?php

declare(strict_types=1);

namespace App\Tests\Field\Integration;

use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Infrastructure\Doctrine\DoctrineCommunityListener;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

final class DoctrineFieldListenerTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;
    private DoctrineCommunityListener $listener;
    public SearchServiceInterface $searchService;
    public SearchHelperInterface $searchHelper;

    protected function setUp(): void
    {
        $this->listener = static::getContainer()->get(DoctrineCommunityListener::class);
        $this->searchService = static::getContainer()->get(SearchServiceInterface::class);
        $this->searchHelper = static::getContainer()->get(SearchHelperInterface::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->searchHelper->deleteIndex(SearchIndex::DIOCESE);
        $this->searchHelper->createIndex(SearchIndex::DIOCESE);
        $this->searchHelper->deleteIndex(SearchIndex::PARISH);
        $this->searchHelper->createIndex(SearchIndex::PARISH);
    }

    public function testPostUpdateParishName(): void
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
        $fieldParishName = DummyFieldFactory::createOne([
            'name' => FieldCommunity::NAME->value,
            Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse du Haillon',
        ]);
        $parish = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese->_real(),
                ]),
                $fieldParishName,
            ],
        ])->_real();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame($document['_source']['parishName'], 'Paroisse du Haillon');
        self::assertSame($document['_source']['dioceseName'], 'Diocèse de Nantes');

        $accessor = Field::getPropertyName(FieldCommunity::NAME);
        $fieldParishName->$accessor = 'Paroisse de la Haie';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame($document['_source']['parishName'], 'Paroisse de la Haie');
        self::assertSame($document['_source']['dioceseName'], 'Diocèse de Nantes');
    }

    public function testPostUpdateDioceseName(): void
    {
        $diocese = flush_after(fn () => DummyCommunityFactory::createOne(
            [
                'fields' => [
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                    ]),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::NAME->value,
                        Field::getPropertyName(FieldCommunity::NAME) => 'Super Diocèse',
                    ]),
                ],
            ]),
        );

        $parish1 = flush_after(fn () => DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse 1',
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese->_real(),
                ]),
            ],
        ]),
        );

        $parish2 = flush_after(fn () => DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse 2',
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese->_real(),
                ]),
            ],
        ]),
        );

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document1 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish1->id->toString());
        self::assertSame($document1['_source']['parishName'], 'Paroisse 1');
        self::assertSame($document1['_source']['dioceseName'], 'Super Diocèse');
        $document2 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish2->id->toString());
        self::assertSame($document2['_source']['parishName'], 'Paroisse 2');
        self::assertSame($document2['_source']['dioceseName'], 'Super Diocèse');

        $dioceseFieldName = $diocese->_real()->getMostTrustableFieldByName(FieldCommunity::NAME);
        $accessor = Field::getPropertyName(FieldCommunity::NAME);
        $dioceseFieldName->$accessor = 'Hyper Diocèse';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document1 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish1->id->toString());
        self::assertSame($document1['_source']['parishName'], 'Paroisse 1');
        self::assertSame($document1['_source']['dioceseName'], 'Hyper Diocèse');
        $document2 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish2->id->toString());
        self::assertSame($document2['_source']['parishName'], 'Paroisse 2');
        self::assertSame($document2['_source']['dioceseName'], 'Hyper Diocèse');
    }
}
