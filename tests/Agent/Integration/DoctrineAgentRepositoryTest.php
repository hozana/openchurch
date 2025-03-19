<?php

declare(strict_types=1);

namespace App\Tests\Agent\Integration;

use App\Agent\Infrastructure\Doctrine\DoctrineAgentRepository;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DoctrineAgentRepositoryTest extends KernelTestCase
{
    use Factories;

    private static EntityManagerInterface $em;

    protected function setUp(): void
    {
        static::$em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFindAgentNameByApiKey(): void
    {
        /** @var DoctrineAgentRepository $repository */
        $repository = static::getContainer()->get(DoctrineAgentRepository::class);

        $agent = DummyAgentFactory::createOne(['name' => 'Romain de rosario', 'apiKey' => '1234']);
        DummyAgentFactory::createOne(['name' => 'toto la praline', 'apiKey' => '5678']);
        DummyAgentFactory::createOne(['name' => 'Nathan de Confessio', 'apiKey' => '89']);
        self::$em->flush();

        $result = $repository->findAgentNameByApiKey($agent->apiKey);

        static::assertEquals($agent->name, $result);
    }
}
