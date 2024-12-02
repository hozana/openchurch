<?php

declare(strict_types=1);

namespace App\Tests\Community\Integration\Doctrine;

use App\Agent\Infrastructure\Doctrine\DoctrineAgentRepository;
use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Model\Community;
use App\Community\Infrastructure\Doctrine\DoctrineCommunityRepository;
use App\Field\Domain\Enum\FieldCommunity;
use App\Shared\Infrastructure\Doctrine\DoctrinePaginator;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DoctrineAgentRepositoryTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private static EntityManagerInterface $em;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
    }

    protected function setUp(): void
    {
        static::$em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFindAgentNameByApiKey(): void
    {
        /** @var DoctrineAgentRepository $repository */
        $repository = static::getContainer()->get(DoctrineAgentRepository::class);
        static::assertEmpty($repository);

        $agent = DummyAgentFactory::createOne(['name' => 'Romain de rosario', 'apiKey' => '1234']);
        DummyAgentFactory::createOne(['name' => 'toto la praline', 'apiKey' => '5678']);
        DummyAgentFactory::createOne(['name' => 'Nathan de Confessio', 'apiKey' => '89']);
        self::$em->flush();

        $result = $repository->findAgentNameByApiKey($agent->apiKey);

        static::assertEquals($agent->name, $result);
    }
}