<?php

declare(strict_types=1);

namespace App\Tests\Field\Integration;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
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
    public SearchHelperInterface $searchHelper;

    protected function setUp(): void
    {
        self::getContainer()->get(DoctrineCommunityListener::class);
        self::getContainer()->get(SearchServiceInterface::class);
        $this->searchHelper = self::getContainer()->get(SearchHelperInterface::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

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
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese,
                ]),
                $fieldParishName,
            ],
        ]);

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse du Haillon', $document['_source']['parishName']);
        self::assertSame('Diocèse de Nantes', $document['_source']['dioceseName']);

        $accessor = Field::getPropertyName(FieldCommunity::NAME);
        $fieldParishName->$accessor = 'Paroisse de la Haie';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse de la Haie', $document['_source']['parishName']);
        self::assertSame('Diocèse de Nantes', $document['_source']['dioceseName']);
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
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese,
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
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese,
                ]),
            ],
        ]),
        );

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document1 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish1->id->toString());
        self::assertSame('Paroisse 1', $document1['_source']['parishName']);
        self::assertSame('Super Diocèse', $document1['_source']['dioceseName']);
        $document2 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish2->id->toString());
        self::assertSame('Paroisse 2', $document2['_source']['parishName']);
        self::assertSame('Super Diocèse', $document2['_source']['dioceseName']);

        $dioceseFieldName = $diocese->getMostTrustableFieldByName(FieldCommunity::NAME);
        $accessor = Field::getPropertyName(FieldCommunity::NAME);
        $dioceseFieldName->$accessor = 'Hyper Diocèse';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document1 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish1->id->toString());
        self::assertSame('Paroisse 1', $document1['_source']['parishName']);
        self::assertSame('Hyper Diocèse', $document1['_source']['dioceseName']);
        $document2 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish2->id->toString());
        self::assertSame('Paroisse 2', $document2['_source']['parishName']);
        self::assertSame('Hyper Diocèse', $document2['_source']['dioceseName']);
    }
}
