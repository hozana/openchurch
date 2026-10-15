<?php

declare(strict_types=1);

namespace App\Tests\Field\Integration;

use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class FieldServiceTest extends KernelTestCase
{
    use Factories;

    /**
     * The update date tells which value is the most recent one: sending the same value again must not
     * make it more recent than the values of the other agents.
     */
    public function testUpdateDateOnlyChangesWithTheField(): void
    {
        $fieldService = self::getContainer()->get(FieldService::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $agent = DummyAgentFactory::createOne();
        $community = DummyCommunityFactory::createOne();

        $fieldService->upsertFields($community, [self::payload('https://a.example')], $agent);
        $em->flush();
        $field = $community->getFieldByNameAndAgent(FieldCommunity::WEBSITE, $agent);
        self::assertInstanceOf(Field::class, $field);
        self::assertNotNull($field->updatedAt);

        $longAgo = new DateTimeImmutable('2020-01-01');
        $field->updatedAt = $longAgo;
        $fieldService->upsertFields($community, [self::payload('https://a.example')], $agent);
        self::assertSame($longAgo, $field->updatedAt);

        $fieldService->upsertFields($community, [self::payload('https://a.example', 'https://a.example/about')], $agent);
        self::assertGreaterThan($longAgo, $field->updatedAt);

        $field->updatedAt = $longAgo;
        $fieldService->upsertFields($community, [self::payload('https://b.example', 'https://a.example/about')], $agent);
        self::assertGreaterThan($longAgo, $field->updatedAt);
    }

    private static function payload(string $website, ?string $explanation = null): Field
    {
        $payload = new Field();
        $payload->name = FieldCommunity::WEBSITE->value;
        $payload->value = $website;
        $payload->engine = FieldEngine::SCRAPER;
        $payload->reliability = FieldReliability::HIGH;
        $payload->explanation = $explanation;

        return $payload;
    }
}
