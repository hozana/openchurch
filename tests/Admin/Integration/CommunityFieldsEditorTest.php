<?php

declare(strict_types=1);

namespace App\Tests\Admin\Integration;

use App\Admin\Application\AdminFieldInput;
use App\Admin\Application\CommunityFieldsEditor;
use App\Admin\Application\CommunityFieldsRejectedException;
use App\Admin\Domain\Model\AdminUser;
use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\FieldValueNormalizer;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityState;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Admin\DummyFactory\DummyAdminUserFactory;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

final class CommunityFieldsEditorTest extends KernelTestCase
{
    use Factories;

    private CommunityFieldsEditor $editor;

    private SearchHelperInterface $searchHelper;

    private EntityManagerInterface $em;

    private Agent $scraper;

    private AdminUser $adminUser;

    public Community $diocese;

    private Community $otherDiocese;

    public Community $parish;

    protected function setUp(): void
    {
        $this->editor = self::getContainer()->get(CommunityFieldsEditor::class);
        $this->searchHelper = self::getContainer()->get(SearchHelperInterface::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        foreach ([SearchIndex::PARISH, SearchIndex::DIOCESE] as $index) {
            $this->searchHelper->deleteIndex($index);
            $this->searchHelper->createIndex($index);
            $this->searchHelper->putMapping($index);
        }

        $this->scraper = DummyAgentFactory::createOne(['name' => 'scraper']);
        $this->adminUser = DummyAdminUserFactory::createOne();
        $this->diocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Nantes');
        $this->otherDiocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Nanterre');
        $this->parish = $this->createCommunity(CommunityType::PARISH, 'Paroisse du Haillon', [
            $this->scraperField(FieldCommunity::PARENT_COMMUNITY_ID, $this->diocese),
            // Invalid (lowercase), as found in the synchronised data: it must not prevent admins from editing
            $this->scraperField(FieldCommunity::CONTACT_COUNTRY_CODE, 'fr'),
        ]);
    }

    public function testAppliedValuesAreWrittenByTheAdminAgentAndWin(): void
    {
        self::assertCount(1, self::getContainer()->get(ValidatorInterface::class)->validate($this->parish));

        $changes = $this->editor->apply($this->parish, $this->adminUser, [
            new AdminFieldInput(FieldCommunity::NAME, 'Paroisse du Haillon-sur-Loire'),
            new AdminFieldInput(FieldCommunity::CONTACT_EMAIL, 'accueil@haillon.example', 'https://haillon.example/contact'),
            new AdminFieldInput(FieldCommunity::WEBSITE, null),
        ]);

        self::assertSame(2, $changes);

        $parish = $this->reload($this->parish);
        $name = $parish->getMostTrustableFieldByName(FieldCommunity::NAME);
        self::assertSame('Paroisse du Haillon-sur-Loire', $name?->getValue());
        self::assertSame(AdminUser::AGENT_NAME, $name->agent->name, 'The value is written on behalf of the agent of the admins');
        self::assertSame(FieldEngine::HUMAN, $name->engine);
        self::assertSame(FieldReliability::HIGH, $name->reliability);
        self::assertSame(CommunityFieldsEditor::SOURCE, $name->source);
        self::assertCount(2, $parish->getFieldsByName(FieldCommunity::NAME), 'The scraper value is kept');

        $email = $parish->getMostTrustableFieldByName(FieldCommunity::CONTACT_EMAIL);
        self::assertSame('accueil@haillon.example', $email?->getValue());
        self::assertSame('https://haillon.example/contact', $email->explanation);

        self::assertSame('Paroisse du Haillon-sur-Loire', $this->parishDocument()['parishName']);
    }

    public function testUnchangedValuesAreNotWrittenAgain(): void
    {
        $inputs = [new AdminFieldInput(FieldCommunity::CONTACT_PHONE, '02 40 00 00 00')];

        self::assertSame(1, $this->editor->apply($this->parish, $this->adminUser, $inputs));
        self::assertSame(0, $this->editor->apply($this->parish, $this->adminUser, $inputs));
    }

    public function testNullValueRemovesTheAdminValue(): void
    {
        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::NAME, 'Nom corrigé')]);
        self::assertSame('Nom corrigé', $this->parishDocument()['parishName']);

        $changes = $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::NAME, null)]);

        self::assertSame(1, $changes);
        $parish = $this->reload($this->parish);
        self::assertCount(1, $parish->getFieldsByName(FieldCommunity::NAME));
        self::assertSame('Paroisse du Haillon', $parish->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue());
        self::assertSame('Paroisse du Haillon', $this->parishDocument()['parishName'], 'The index falls back on the scraper value');
    }

    public function testParentChangeIsIndexed(): void
    {
        self::assertSame($this->diocese->id?->toString(), $this->parishDocument()['dioceseId']);

        $this->editor->apply($this->parish, $this->adminUser, [
            new AdminFieldInput(FieldCommunity::PARENT_COMMUNITY_ID, $this->otherDiocese->id?->toString()),
        ]);

        $document = $this->parishDocument();
        self::assertSame($this->otherDiocese->id?->toString(), $document['dioceseId']);
        self::assertSame('Diocèse de Nanterre', $document['dioceseName']);
    }

    public function testRelatedCommunities(): void
    {
        $replaced = $this->createCommunity(CommunityType::PARISH, 'Ancienne paroisse');

        $this->editor->apply($this->parish, $this->adminUser, [
            new AdminFieldInput(FieldCommunity::REPLACES, FieldValueNormalizer::normalize([$replaced->id?->toString(), strtoupper((string) $replaced->id?->toString())])),
        ]);

        $replaces = $this->reload($this->parish)->getMostTrustableFieldByName(FieldCommunity::REPLACES)?->getValue();
        self::assertIsArray($replaces);
        self::assertSame([$replaced->id?->toString()], array_map(static fn (Community $community) => $community->id?->toString(), $replaces));
    }

    public function testRejectedChangesAreNotWritten(): void
    {
        try {
            $this->editor->apply($this->parish, $this->adminUser, [
                new AdminFieldInput(FieldCommunity::CONTACT_CITY, 'Nantes'),
                new AdminFieldInput(FieldCommunity::STATE, CommunityState::DELETED->value),
            ]);
            self::fail('A deleted state without deletion reason must be rejected');
        } catch (CommunityFieldsRejectedException $e) {
            self::assertSame(['Deletion reason is mandatory when reporting a state=deleted state.'], $e->errors);
        }

        $parish = $this->reload($this->parish);
        self::assertCount(0, $parish->getFieldsByName(FieldCommunity::CONTACT_CITY));
        self::assertCount(0, $parish->getFieldsByName(FieldCommunity::STATE));
    }

    public function testNewViolationOfAnExistingKindIsRejected(): void
    {
        $this->expectException(CommunityFieldsRejectedException::class);
        $this->expectExceptionMessage("Country code 'fr' is not valid.");

        // The scraper already stores this invalid value, but the admin must not add one more
        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::CONTACT_COUNTRY_CODE, 'fr')]);
    }

    public function testUnknownRelatedCommunityIsRejected(): void
    {
        $this->expectException(CommunityFieldsRejectedException::class);

        $this->editor->apply($this->parish, $this->adminUser, [
            new AdminFieldInput(FieldCommunity::PARENT_COMMUNITY_ID, Uuid::v7()->toString()),
        ]);
    }

    /**
     * All the admins write on behalf of the same agent: the last change replaces the previous one.
     */
    public function testAdminsShareTheAdminValue(): void
    {
        $otherAdmin = DummyAdminUserFactory::createOne();
        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::WEBSITE, 'https://first.example')]);
        $this->editor->apply($this->parish, $otherAdmin, [new AdminFieldInput(FieldCommunity::WEBSITE, 'https://second.example')]);

        $parish = $this->reload($this->parish);
        self::assertCount(1, $parish->getFieldsByName(FieldCommunity::WEBSITE));
        self::assertSame('https://second.example', $parish->getMostTrustableFieldByName(FieldCommunity::WEBSITE)?->getValue());
    }

    public function testResettingAReasonTheAdminStateRequiresIsRejected(): void
    {
        $this->editor->apply($this->parish, $this->adminUser, [
            new AdminFieldInput(FieldCommunity::STATE, 'deleted'),
            new AdminFieldInput(FieldCommunity::DELETION_REASON, 'duplicate'),
        ]);

        $this->expectException(CommunityFieldsRejectedException::class);
        $this->expectExceptionMessage('Deletion reason is mandatory when reporting a state=deleted state.');

        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::DELETION_REASON, null)]);
    }

    public function testExplanationAloneIsRejected(): void
    {
        $this->expectException(CommunityFieldsRejectedException::class);
        $this->expectExceptionMessage('« Email » : une explication ne peut accompagner qu\'une valeur.');

        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::CONTACT_EMAIL, null, 'https://haillon.example')]);
    }

    public function testTooLongValueIsRejected(): void
    {
        $this->expectException(CommunityFieldsRejectedException::class);
        $this->expectExceptionMessage('Field website cannot be longer than 255 characters');

        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::WEBSITE, 'https://example.com/'.str_repeat('a', 300))]);
    }

    /**
     * @dataProvider invalidRelationProvider
     *
     * @param callable(self): (string|list<string>) $value
     */
    public function testInvalidRelationsAreRejected(FieldCommunity $name, callable $value, string $error): void
    {
        $this->expectException(CommunityFieldsRejectedException::class);
        $this->expectExceptionMessage($error);

        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput($name, $value($this))]);
    }

    /**
     * @return iterable<string, array{0: FieldCommunity, 1: callable(self): (string|list<string>), 2: string}>
     */
    public static function invalidRelationProvider(): iterable
    {
        yield 'parish as its own parent' => [FieldCommunity::PARENT_COMMUNITY_ID, static fn (self $test) => (string) $test->parish->id?->toString(), '« Diocèse » : une paroisse ne peut pas se désigner elle-même.'];
        yield 'uppercase id of the parish itself' => [FieldCommunity::REPLACES, static fn (self $test) => [strtoupper((string) $test->parish->id?->toString())], '« Remplace » : une paroisse ne peut pas se désigner elle-même.'];
        yield 'parish as parent' => [FieldCommunity::PARENT_COMMUNITY_ID, static fn (self $test) => (string) $test->createCommunity(CommunityType::PARISH, 'Paroisse voisine')->id?->toString(), '« Diocèse » : « Paroisse voisine » n\'est pas un diocèse.'];
        yield 'diocese as replaced parish' => [FieldCommunity::REPLACES, static fn (self $test) => [(string) $test->diocese->id?->toString()], '« Remplace » : « Diocèse de Nantes » n\'est pas une paroisse.'];
        yield 'unknown id which is not a v7 uuid' => [FieldCommunity::REPLACES, static fn () => [Uuid::v4()->toString()], '« Remplace » : communauté introuvable.'];
    }

    public function testReadOnlyFieldsCannotBeEdited(): void
    {
        $this->expectException(LogicException::class);

        $this->editor->apply($this->parish, $this->adminUser, [new AdminFieldInput(FieldCommunity::WIKIDATA_ID, 42)]);
    }

    /**
     * @param list<Field> $extraFields
     */
    public function createCommunity(CommunityType $type, string $name, array $extraFields = []): Community
    {
        return DummyCommunityFactory::createOne(['fields' => [
            $this->scraperField(FieldCommunity::TYPE, $type->value),
            $this->scraperField(FieldCommunity::NAME, $name),
            ...$extraFields,
        ]]);
    }

    private function scraperField(FieldCommunity $name, mixed $value): Field
    {
        return DummyFieldFactory::createOne([
            'name' => $name->value,
            Field::getPropertyName($name) => $value,
            'agent' => $this->scraper,
            'engine' => FieldEngine::SCRAPER,
            'reliability' => FieldReliability::HIGH,
        ]);
    }

    private function reload(Community $community): Community
    {
        $this->em->clear();
        $reloaded = $this->em->find(Community::class, $community->id);
        self::assertInstanceOf(Community::class, $reloaded);

        return $reloaded;
    }

    /**
     * @return array<string, mixed>
     */
    private function parishDocument(): array
    {
        $this->searchHelper->refresh(SearchIndex::PARISH);
        $document = $this->searchHelper->getDocument(SearchIndex::PARISH, (string) $this->parish->id?->toString());
        self::assertIsArray($document);
        self::assertIsArray($document['_source']);

        return $document['_source'];
    }
}
