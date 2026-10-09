<?php

declare(strict_types=1);

namespace App\Tests\Field\Unit;

use App\Field\Domain\FieldValueNormalizer;
use App\FieldHolder\Community\Domain\Model\Community;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Uid\Uuid;

final class FieldValueNormalizerTest extends TestCase
{
    public function testEmptyValuesBecomeNull(): void
    {
        self::assertNull(FieldValueNormalizer::normalize(null));
        self::assertNull(FieldValueNormalizer::normalize(''));
        self::assertNull(FieldValueNormalizer::normalize([]));
        self::assertNull(FieldValueNormalizer::normalize(new ArrayCollection()));
    }

    public function testScalarsAreKept(): void
    {
        self::assertSame('Paroisse Saint-Pierre', FieldValueNormalizer::normalize('Paroisse Saint-Pierre'));
        self::assertSame(42, FieldValueNormalizer::normalize(42));
        self::assertSame(1.5, FieldValueNormalizer::normalize(1.5));
    }

    public function testDatesBecomeStrings(): void
    {
        self::assertSame('2026-09-30 12:34:56', FieldValueNormalizer::normalize(new DateTimeImmutable('2026-09-30 12:34:56')));
    }

    public function testRelatedCommunitiesBecomeIds(): void
    {
        $community = new Community();
        $community->id = Uuid::v7();
        $other = new Community();
        $other->id = Uuid::v7();

        self::assertSame($community->id->toString(), FieldValueNormalizer::normalize($community));
        self::assertSame(
            [$community->id->toString(), $other->id->toString()],
            FieldValueNormalizer::normalize(new ArrayCollection([$community, $other])),
        );
        self::assertSame(['some-id'], FieldValueNormalizer::normalize(['some-id']));
    }

    public function testRelatedIdsAreCanonicalAndUnique(): void
    {
        $id = Uuid::v7()->toString();

        self::assertSame([$id], FieldValueNormalizer::normalize([$id, strtoupper($id), $id]));
    }

    public function testUnsupportedValuesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FieldValueNormalizer::normalize(new stdClass());
    }
}
