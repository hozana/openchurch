<?php

declare(strict_types=1);

namespace App\Tests\Field\Integration;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\FieldHolder\Place\DummyFactory\DummyPlaceFactory;
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
        $fieldParishName->{$accessor} = 'Paroisse de la Haie';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse de la Haie', $document['_source']['parishName']);
        self::assertSame('Diocèse de Nantes', $document['_source']['dioceseName']);
    }

    public function testPostUpdateDioceseName(): void
    {
        $diocese = flush_after(static fn () => DummyCommunityFactory::createOne(
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

        $parish1 = flush_after(static fn () => DummyCommunityFactory::createOne([
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

        $parish2 = flush_after(static fn () => DummyCommunityFactory::createOne([
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
        $dioceseFieldName->{$accessor} = 'Hyper Diocèse';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document1 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish1->id->toString());
        self::assertSame('Paroisse 1', $document1['_source']['parishName']);
        self::assertSame('Hyper Diocèse', $document1['_source']['dioceseName']);
        $document2 = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish2->id->toString());
        self::assertSame('Paroisse 2', $document2['_source']['parishName']);
        self::assertSame('Hyper Diocèse', $document2['_source']['dioceseName']);
    }

    /**
     * A more trustable agent adding its own name (a new Field row, not an update) must be reflected
     * in the index, and removing it must restore the previous name.
     */
    public function testPostPersistAndPostRemoveOfAMoreTrustableName(): void
    {
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
            ],
        ]);

        $humanName = DummyFieldFactory::createOne([
            'name' => FieldCommunity::NAME->value,
            Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse du Haillon-sur-Loire',
            'engine' => FieldEngine::HUMAN,
            'reliability' => FieldReliability::HIGH,
        ]);
        $parish->addField($humanName);
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse du Haillon-sur-Loire', $document['_source']['parishName']);

        $parish->fields->removeElement($humanName);
        $this->em->remove($humanName);
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse du Haillon', $document['_source']['parishName']);
    }

    /**
     * Renaming a diocese must not rename it in the documents of the parishes that some agent still attaches
     * to it, but whose retained parent is another diocese.
     */
    public function testRenamingADioceseOnlyAffectsTheParishesWhoseRetainedParentItIs(): void
    {
        $dioceses = [];
        foreach (['Diocèse de Nantes', 'Diocèse de Lyon'] as $name) {
            $dioceses[] = DummyCommunityFactory::createOne(['fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => $name,
                ]),
            ]]);
        }
        [$nantes, $lyon] = $dioceses;

        $parish = DummyCommunityFactory::createOne(['fields' => [
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
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $nantes,
            ]),
            // A more trustable agent moved the parish to Lyon
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $lyon,
                'reliability' => FieldReliability::HIGH,
            ]),
        ]]);

        $nantes->getMostTrustableFieldByName(FieldCommunity::NAME)->{Field::getPropertyName(FieldCommunity::NAME)} = 'Diocèse de Nantes (renommé)';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame($lyon->id->toString(), $document['_source']['dioceseId']);
        self::assertSame('Diocèse de Lyon', $document['_source']['dioceseName']);
    }

    /**
     * Communities attached to a diocese are not all parishes: renaming the diocese must not put the others
     * in the parish index.
     */
    public function testRenamingADioceseDoesNotIndexItsNonParishChildren(): void
    {
        $diocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Nantes');
        $deanery = $this->createCommunity(CommunityType::DEANERY, 'Doyenné de Nantes-Centre', $diocese);

        $diocese->getMostTrustableFieldByName(FieldCommunity::NAME)->{Field::getPropertyName(FieldCommunity::NAME)} = 'Diocèse de Nantes (renommé)';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        self::assertNull($this->searchHelper->getDocument(SearchIndex::PARISH, $deanery->id->toString()));
    }

    /**
     * A parish which becomes another kind of community leaves the parish index.
     */
    public function testTypeChangeRemovesTheCommunityFromItsFormerIndex(): void
    {
        $diocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Nantes');
        $parish = $this->createCommunity(CommunityType::PARISH, 'Paroisse du Haillon', $diocese);
        $this->searchHelper->refresh(SearchIndex::PARISH);
        self::assertNotNull($this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString()));

        $parish->getMostTrustableFieldByName(FieldCommunity::TYPE)->{Field::getPropertyName(FieldCommunity::TYPE)} = CommunityType::DEANERY->value;
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        self::assertNull($this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString()));
    }

    /**
     * Updating a less trustable name must not override the most trustable one in the index.
     */
    public function testPostUpdateOfALessTrustableNameKeepsTheMostTrustableOne(): void
    {
        $lowName = DummyFieldFactory::createOne([
            'name' => FieldCommunity::NAME->value,
            Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse (source peu fiable)',
            'reliability' => FieldReliability::LOW,
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
                    'reliability' => FieldReliability::HIGH,
                ]),
                $lowName,
            ],
        ]);

        $lowName->{Field::getPropertyName(FieldCommunity::NAME)} = 'Paroisse (autre source peu fiable)';
        $this->em->flush();

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse du Haillon', $document['_source']['parishName']);
    }

    private function createCommunity(CommunityType $type, string $name, ?Community $parent = null): Community
    {
        $fields = [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::TYPE->value,
                Field::getPropertyName(FieldCommunity::TYPE) => $type->value,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => $name,
            ]),
        ];
        if (null !== $parent) {
            $fields[] = DummyFieldFactory::createOne([
                'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $parent,
            ]);
        }

        return DummyCommunityFactory::createOne(['fields' => $fields]);
    }

    /**
     * FieldPlace::NAME and FieldCommunity::NAME share the same 'name' value, so this listener also
     * fires for fields attached to a Place, where $field->community is null. Renaming a place must
     * not blow up just because the community-oriented indexing has nothing to do.
     */
    public function testPostUpdateOnAPlaceNameDoesNotFail(): void
    {
        $fieldPlaceName = DummyFieldFactory::createOne([
            'name' => FieldPlace::NAME->value,
            Field::getPropertyName(FieldPlace::NAME) => 'Église Saint-Pierre',
        ]);
        DummyPlaceFactory::createOne([
            'fields' => [$fieldPlaceName],
        ]);

        $accessor = Field::getPropertyName(FieldPlace::NAME);
        $fieldPlaceName->{$accessor} = 'Église Saint-Paul';
        $this->em->flush();

        self::assertSame('Église Saint-Paul', $fieldPlaceName->{$accessor});
    }
}
