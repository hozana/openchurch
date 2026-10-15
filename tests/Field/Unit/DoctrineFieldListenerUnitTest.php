<?php

declare(strict_types=1);

namespace App\Tests\Field\Unit;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\Field\Infrastructure\Doctrine\DoctrineFieldListener;
use App\FieldHolder\Community\Application\CommunitySearchIndexer;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class DoctrineFieldListenerUnitTest extends TestCase
{
    /**
     * @dataProvider failureProvider
     */
    public function testIndexingFailuresAreLoggedInsteadOfFailingTheWrite(Throwable $failure): void
    {
        $searchHelper = $this->createMock(SearchHelperInterface::class);
        $searchHelper->expects(self::once())->method('bulkWrite')->willThrowException($failure);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $listener = new DoctrineFieldListener(
            'synchro secret',
            $this->createStub(Security::class),
            new CommunitySearchIndexer($searchHelper, $this->createStub(CommunityRepositoryInterface::class)),
            $logger,
        );

        $parish = new Community();
        $parish->id = Uuid::v7();
        foreach ([FieldCommunity::TYPE->value => CommunityType::PARISH->value, FieldCommunity::NAME->value => 'Paroisse du Haillon'] as $name => $value) {
            $field = new Field();
            $field->name = $name;
            $field->stringVal = $value;
            $field->reliability = 'high';
            $field->engine = 'scraper';
            $parish->addField($field);
            $listener->onFieldChange($field);
        }

        $listener->postFlush();
    }

    /**
     * @return iterable<string, array{Throwable}>
     */
    public static function failureProvider(): iterable
    {
        yield 'Elasticsearch down' => [new ClientResponseException('Elasticsearch is down')];
        yield 'any other failure' => [new RuntimeException('Unexpected')];
    }
}
