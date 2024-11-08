<?php

namespace App\Test\Integration\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\Model\FieldFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CommunityCollectionProviderTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        FieldFactory::createMany(5, ['name' => 'type', 'value' => 'parish']);
    }
}