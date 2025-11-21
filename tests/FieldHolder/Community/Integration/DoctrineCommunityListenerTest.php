<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Integration;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\FieldHolder\Community\Infrastructure\Doctrine\DoctrineCommunityListener;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DoctrineCommunityListenerTest extends KernelTestCase
{
    use Factories;
    public SearchHelperInterface $searchHelper;

    protected function setUp(): void
    {
        self::getContainer()->get(DoctrineCommunityListener::class);
        self::getContainer()->get(SearchServiceInterface::class);
        $this->searchHelper = self::getContainer()->get(SearchHelperInterface::class);

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
                    Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese,
                ]),
            ],
        ]);
        $this->searchHelper->refresh(SearchIndex::PARISH);

        $parish = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString());
        self::assertSame('Paroisse du Haillon', $parish['_source']['parishName']);
        self::assertSame('Diocèse de Nantes', $parish['_source']['dioceseName']);

        $this->searchHelper->getDocument(SearchIndex::DIOCESE, $diocese->id->toString());
        self::assertSame('Diocèse de Nantes', $parish['_source']['dioceseName']);
    }
}
