<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Integration;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Infrastructure\Doctrine\DoctrineCommunityRepository;
use App\Shared\Infrastructure\Doctrine\DoctrinePaginator;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DoctrineCommunityRepositoryTest extends KernelTestCase
{
    use Factories;

    private static EntityManagerInterface $em;

    protected function setUp(): void
    {
        static::$em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSave(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);
        static::assertCount(0, $repository);

        $community = DummyCommunityFactory::createOne();
        $repository->add($community);
        self::$em->flush();

        static::assertCount(1, $repository);
    }

    public function testOfId(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        static::assertCount(0, $repository);

        $community = DummyCommunityFactory::createOne();
        $repository->add($community);
        self::$em->flush();

        static::assertEquals($community, $repository->ofId($community->id));
    }

    public function testOfIds(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        static::assertCount(0, $repository);
        $community1 = DummyCommunityFactory::createOne();
        DummyCommunityFactory::createOne();
        DummyCommunityFactory::createOne();

        self::$em->flush();
        static::assertCount(1, $repository->ofIds([$community1->id]));
    }

    public function testWithType(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        DummyCommunityFactory::createMany(2, ['fields' => [DummyFieldFactory::new(['name' => FieldCommunity::TYPE->value, 'stringVal' => CommunityType::DIOCESE->value])]]);
        DummyCommunityFactory::createOne(['fields' => [DummyFieldFactory::new(['name' => FieldCommunity::TYPE->value, 'stringVal' => CommunityType::PARISH->value])]]);

        self::$em->flush();
        $results = $repository->withType(CommunityType::DIOCESE->value);
        static::assertCount(2, $results);

        foreach ($results as $result) {
            self::assertSame(CommunityType::DIOCESE->value, $result->fields[0]->stringVal);
        }
    }

    public function testWikidataId(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        $field1 = DummyFieldFactory::createOne(['name' => FieldCommunity::WIKIDATA_ID->value, 'intVal' => 1]);
        $field2 = DummyFieldFactory::createOne(['name' => FieldCommunity::WIKIDATA_ID->value, 'intVal' => 2]);
        $field3 = DummyFieldFactory::createOne(['name' => FieldCommunity::WIKIDATA_ID->value, 'intVal' => 3]);

        DummyCommunityFactory::createOne(['fields' => [$field1]]);
        DummyCommunityFactory::createOne(['fields' => [$field2]]);
        DummyCommunityFactory::createOne(['fields' => [$field3]]);

        self::$em->flush();

        $results = $repository->withWikidataId(3);
        static::assertCount(1, $results);

        foreach ($results as $result) {
            self::assertSame(3, $result->fields[0]->intVal);
        }
    }

    public function testContactZipcodes(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        $field1 = DummyFieldFactory::createOne(['name' => FieldCommunity::CONTACT_ZIPCODE->value, 'stringVal' => '75001']);
        $field2 = DummyFieldFactory::createOne(['name' => FieldCommunity::CONTACT_ZIPCODE->value, 'stringVal' => '40270']);
        $field3 = DummyFieldFactory::createOne(['name' => FieldCommunity::CONTACT_ZIPCODE->value, 'stringVal' => '30000']);

        DummyCommunityFactory::createOne(['fields' => [$field1]]);
        $communityGrenade = DummyCommunityFactory::createOne(['fields' => [$field2]]);
        $communityNimes = DummyCommunityFactory::createOne(['fields' => [$field3]]);

        self::$em->flush();

        $results = $repository->withContactZipcodes(['40270', '30000']);
        static::assertCount(2, $results);

        static::assertSame($communityGrenade, $results->asCollection()->get(0));
        static::assertSame($communityNimes, $results->asCollection()->get(1));
    }

    public function testAddSelectField(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        static::assertStringNotContainsString('JOIN', $repository->getDQL());
        static::assertStringNotContainsString('fields', $repository->getDQL());

        $result = $repository->addSelectField();
        static::assertStringContainsString('JOIN', $result->getDQL());
        static::assertStringContainsString('fields', $result->getDQL());
    }

    public function testAsCollection(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        static::assertCount(0, $repository);
        $community1 = DummyCommunityFactory::createOne();
        DummyCommunityFactory::createOne();
        DummyCommunityFactory::createOne();

        self::$em->flush();
        static::assertInstanceOf(Collection::class, $repository->ofIds([$community1->id])->asCollection());
    }

    public function testWithPagination(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);
        static::assertNull($repository->paginator());

        $repository = $repository->withPagination(1, 2);

        static::assertInstanceOf(DoctrinePaginator::class, $repository->paginator());
    }

    public function testWithoutPagination(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);
        $repository = $repository->withPagination(1, 2);
        static::assertNotNull($repository->paginator());

        $repository = $repository->withoutPagination();
        static::assertNull($repository->paginator());
    }

    public function testIteratorWithoutPagination(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);
        static::assertNull($repository->paginator());

        $communities = DummyCommunityFactory::createMany(3);
        foreach ($communities as $community) {
            $repository->add($community);
        }
        self::$em->flush();

        $i = 0;
        foreach ($repository as $community) {
            static::assertSame($communities[$i], $community);
            ++$i;
        }
    }

    public function testIteratorWithPagination(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);
        static::assertNull($repository->paginator());

        $communities = array_map(
            fn (Community $community) => $community,
            DummyCommunityFactory::createMany(3)
        );

        foreach ($communities as $community) {
            $repository->add($community);
        }
        self::$em->flush();

        $repository = $repository->withPagination(1, 2);

        $i = 0;
        foreach ($repository as $community) {
            static::assertContains($community, $communities);
            ++$i;
        }

        static::assertSame(2, $i);

        $repository = $repository->withPagination(2, 2);

        $i = 0;
        foreach ($repository as $community) {
            static::assertContains($community, $communities);
            ++$i;
        }

        static::assertSame(1, $i);
    }

    public function testCount(): void
    {
        /** @var DoctrineCommunityRepository $repository */
        $repository = static::getContainer()->get(DoctrineCommunityRepository::class);

        $communities = array_map(
            fn (Community $community) => $community,
            DummyCommunityFactory::createMany(3)
        );
        foreach ($communities as $community) {
            $repository->add($community);
        }
        self::$em->flush();

        static::assertCount(count($communities), $repository);
        static::assertCount(2, $repository->withPagination(1, 2));
    }
}
