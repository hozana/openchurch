<?php

declare(strict_types=1);

namespace App\Tests\Admin\Acceptance;

use App\Admin\Domain\Model\AdminUser;
use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityDeletionReason;
use App\FieldHolder\Community\Domain\Enum\CommunityState;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Admin\DummyFactory\DummyAdminUserFactory;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

final class AdminParishTest extends WebTestCase
{
    use Factories;

    private KernelBrowser $client;

    private Agent $scraper;

    private AdminUser $adminUser;

    private Community $diocese;

    private Community $parish;

    private Community $otherParish;

    private Community $otherDiocese;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $searchHelper = self::getContainer()->get(SearchHelperInterface::class);
        foreach ([SearchIndex::PARISH, SearchIndex::DIOCESE] as $index) {
            $searchHelper->deleteIndex($index);
            $searchHelper->createIndex($index);
            $searchHelper->putMapping($index);
        }

        $this->scraper = DummyAgentFactory::createOne(['name' => 'scraper']);
        $this->adminUser = DummyAdminUserFactory::createOne(['email' => 'admin@openchurch.test']);
        $this->diocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Nantes');
        $this->otherDiocese = $this->createCommunity(CommunityType::DIOCESE, 'Diocèse de Lyon');
        $this->parish = $this->createCommunity(CommunityType::PARISH, 'Paroisse Saint-Joseph du Haillon', $this->diocese);
        $this->otherParish = $this->createCommunity(CommunityType::PARISH, 'Paroisse Sainte-Blandine', $this->otherDiocese);

        $searchHelper->refresh(SearchIndex::PARISH);
        $searchHelper->refresh(SearchIndex::DIOCESE);
    }

    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/parishes');

        self::assertResponseRedirects('http://localhost/admin/login');
    }

    public function testAgentApiKeysDoNotGrantAccess(): void
    {
        $this->client->request('GET', '/admin/parishes', server: ['HTTP_AUTHORIZATION' => "Bearer {$this->scraper->apiKey}"]);

        self::assertResponseRedirects('http://localhost/admin/login');
    }

    public function testLogin(): void
    {
        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'admin@openchurch.test',
            '_password' => DummyAdminUserFactory::PASSWORD,
        ]);
        self::assertResponseRedirects('/admin');

        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/parishes');

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Paroisses');
    }

    public function testLoginWithAWrongPassword(): void
    {
        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'admin@openchurch.test',
            '_password' => 'wrong password',
        ]);
        $this->client->followRedirect();

        self::assertSelectorExists('.alert-danger');
        $this->client->request('GET', '/admin/parishes');
        self::assertResponseRedirects('http://localhost/admin/login');
    }

    public function testSearchByText(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'joseph']);

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('table.datagrid tbody tr');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Paroisse Saint-Joseph du Haillon', $rows->text());
        self::assertStringContainsString('Diocèse de Nantes', $rows->text());
        self::assertSame((string) $this->parish->id, $rows->filter('td.id')->text());
        self::assertSame("/admin/parishes/{$this->parish->id}/edit?q=joseph", $rows->filter('a.action-edit')->attr('href'));
    }

    public function testFilterByDiocese(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes', ['diocese' => $this->diocese->id?->toString()]);

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('table.datagrid tbody tr');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Paroisse Saint-Joseph du Haillon', $rows->text());
        self::assertSelectorTextContains('#parish-search-diocese option[selected]', 'Diocèse de Nantes');
    }

    public function testListShowsTheState(): void
    {
        $this->parish->addField($this->scraperField(FieldCommunity::STATE, 'deleted'));
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->client->loginUser($this->adminUser, 'admin');

        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'joseph']);
        self::assertSame('Supprimée', $crawler->filter('table.datagrid tbody tr td.state')->text());

        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'blandine']);
        self::assertSame('—', $crawler->filter('table.datagrid tbody tr td.state')->text(), 'Without state, nothing is displayed');
    }

    public function testEditAndReset(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";
        $crawler = $this->client->request('GET', $editUrl);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Paroisse Saint-Joseph du Haillon');
        self::assertSelectorTextContains('tr[data-field="wikidataId"]', 'Lecture seule');
        self::assertSelectorNotExists('[name="community_fields[wikidataId][value]"]');

        $form = $crawler->filter('#community-fields-form')->form([
            'community_fields[contactEmail][value]' => 'accueil@haillon.example',
            'community_fields[contactEmail][explanation]' => 'https://haillon.example/contact',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects($editUrl);

        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', '1 champ(s) enregistré(s).');
        self::assertSelectorTextContains('tr[data-field="contactEmail"]', 'accueil@haillon.example');
        self::assertSelectorTextContains('tr[data-field="contactEmail"]', 'retenue');
        self::assertSame(['accueil@haillon.example'], $this->storedValues(FieldCommunity::CONTACT_EMAIL));

        $resetForm = $crawler->filter('form#reset-contactEmail')->form();
        $this->client->submit($resetForm);
        self::assertResponseRedirects($editUrl);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'La valeur admin du champ « Email » a été supprimée.');
        self::assertSelectorTextContains('tr[data-field="contactEmail"]', 'Aucune valeur');
        self::assertSame([], $this->storedValues(FieldCommunity::CONTACT_EMAIL));
    }

    public function testCreate(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes');
        $crawler = $this->client->click($crawler->selectLink('Créer une paroisse')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Nouvelle paroisse');
        self::assertPageTitleContains('Nouvelle paroisse');
        self::assertSelectorNotExists('[name="community_fields[type][value]"]', 'The type is always a parish');
        self::assertSelectorNotExists('[name="community_fields[wikidataId][value]"]');
        self::assertCount(1, $crawler->filter('legend.required'), 'Only the name is marked as required');
        self::assertSelectorTextContains('legend.required', 'Nom');

        $form = $crawler->filter('#community-fields-form')->form([
            'community_fields[name][value]' => 'Paroisse de la Chapelle',
            'community_fields[contactEmail][value]' => 'accueil@chapelle.example',
            'community_fields[contactEmail][explanation]' => 'https://chapelle.example/contact',
        ]);
        // The options come from the autocomplete: the page only holds the selected ones
        $form['community_fields[parentCommunityId][value]']->disableValidation()->setValue((string) $this->diocese->id?->toString());
        $this->client->submit($form);

        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#^/admin/parishes/[0-9a-f-]{36}/edit$#', $location);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'La paroisse a été créée.');
        self::assertSelectorTextContains('h1', 'Paroisse de la Chapelle');
        self::assertSelectorTextContains('tr[data-field="type"]', 'parish');
        self::assertSelectorTextContains('tr[data-field="contactEmail"]', 'accueil@chapelle.example');
        self::assertSelectorTextContains('tr[data-field="parentCommunityId"]', 'Diocèse de Nantes');

        self::getContainer()->get(SearchHelperInterface::class)->refresh(SearchIndex::PARISH);
        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'chapelle']);
        $rows = $crawler->filter('table.datagrid tbody tr');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Paroisse de la Chapelle', $rows->text());
        self::assertStringContainsString('Diocèse de Nantes', $rows->text());
    }

    public function testCreateWithoutANameIsRejected(): void
    {
        $communities = $this->communityCount();
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes/new');

        $form = $crawler->filter('#community-fields-form')->form(['community_fields[contactCity][value]' => 'Nantes']);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#community_fields_name', 'Ce champ est obligatoire pour créer une paroisse.');
        self::assertInputValueSame('community_fields[contactCity][value]', 'Nantes', 'The input of the admin is kept');
        self::assertSame($communities, $this->communityCount());
    }

    public function testCreationRejectedByTheEditorDisplaysTheErrors(): void
    {
        $communities = $this->communityCount();
        $this->client->loginUser($this->adminUser, 'admin');
        $form = $this->client->request('GET', '/admin/parishes/new')->filter('#community-fields-form')->form(['community_fields[name][value]' => 'Paroisse de la Chapelle']);
        // The options come from the autocomplete: the page only holds the selected ones
        $form['community_fields[parentCommunityId][value]']->disableValidation()->setValue((string) $this->otherParish->id?->toString());
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#community-fields-form', '« Diocèse » : « Paroisse Sainte-Blandine » n\'est pas un diocèse.');
        self::assertInputValueSame('community_fields[name][value]', 'Paroisse de la Chapelle', 'The input of the admin is kept');
        self::assertSame($communities, $this->communityCount());
    }

    public function testCreationFormCsrfProtection(): void
    {
        $communities = $this->communityCount();
        $this->client->loginUser($this->adminUser, 'admin');
        $this->client->request('POST', '/admin/parishes/new', [
            'community_fields' => ['name' => ['value' => 'Paroisse de la Chapelle'], '_token' => 'csrf-token'],
        ], server: ['HTTP_ORIGIN' => 'https://attacker.example']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame($communities, $this->communityCount());
    }

    public function testRejectedCreationIsNotWritten(): void
    {
        $communities = $this->communityCount();
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes/new');

        $form = $crawler->filter('#community-fields-form')->form([
            'community_fields[name][value]' => 'Paroisse de la Chapelle',
            'community_fields[state][value]' => 'deleted',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#community_fields_deletionReason', 'Un motif de suppression est obligatoire pour une paroisse supprimée.');
        self::assertInputValueSame('community_fields[name][value]', 'Paroisse de la Chapelle', 'The input of the admin is kept');
        self::assertSame($communities, $this->communityCount());
    }

    public function testCreationRequiresAnAdmin(): void
    {
        $communities = $this->communityCount();
        $this->client->request('GET', '/admin/parishes/new');
        self::assertResponseRedirects('http://localhost/admin/login');

        $this->client->request('POST', '/admin/parishes/new', server: ['HTTP_AUTHORIZATION' => "Bearer {$this->scraper->apiKey}"]);
        self::assertResponseRedirects('http://localhost/admin/login');
        self::assertSame($communities, $this->communityCount());
    }

    public function testTheListSearchIsKeptWhileCreating(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'joseph']);
        self::assertSame('/admin/parishes/new?q=joseph', $crawler->selectLink('Créer une paroisse')->attr('href'));

        $crawler = $this->client->click($crawler->selectLink('Créer une paroisse')->link());
        self::assertSame('/admin/parishes?q=joseph', $crawler->selectLink('Retour à la liste')->attr('href'));

        $this->client->submit($crawler->filter('#community-fields-form')->form(['community_fields[name][value]' => 'Paroisse de la Chapelle']));
        self::assertMatchesRegularExpression('#^/admin/parishes/[0-9a-f-]{36}/edit\?q=joseph$#', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testTheListSearchIsKeptWhileEditing(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'joseph']);
        $crawler = $this->client->click($crawler->filter('a.action-edit')->link());

        $form = $crawler->filter('#community-fields-form')->form(['community_fields[contactCity][value]' => 'Nantes']);
        $this->client->submit($form);
        self::assertResponseRedirects("/admin/parishes/{$this->parish->id}/edit?q=joseph");

        $crawler = $this->client->followRedirect();
        self::assertSame('/admin/parishes?q=joseph', $crawler->selectLink('Retour à la liste')->attr('href'));
    }

    public function testEditARelationThroughTheForm(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";
        $form = $this->client->request('GET', $editUrl)->filter('#community-fields-form')->form();
        // The options come from the autocomplete: the page only holds the selected ones
        $form['community_fields[parentCommunityId][value]']->disableValidation()->setValue((string) $this->otherDiocese->id?->toString());
        $this->client->submit($form);
        self::assertResponseRedirects($editUrl);

        $this->client->followRedirect();
        self::assertSelectorTextContains('tr[data-field="parentCommunityId"]', 'Diocèse de Lyon');
        self::assertSelectorTextContains('#community_fields_parentCommunityId_value option[selected]', 'Diocèse de Lyon');
    }

    public function testReplacedParishesAreDeletedAndLinked(): void
    {
        $remy = $this->createCommunity(CommunityType::PARISH, 'Saint Remy des trois rivières', $this->diocese);
        $searchHelper = self::getContainer()->get(SearchHelperInterface::class);
        $searchHelper->refresh(SearchIndex::PARISH);
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";

        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'parish', 'query' => 'remy']);
        self::assertCount(1, json_decode((string) $this->client->getResponse()->getContent(), true)['results'], 'An alive parish is offered');

        $crawler = $this->client->request('GET', $editUrl);
        self::assertSelectorTextContains('#community-fields-form', 'Les paroisses sélectionnées sont supprimées (motif : dissoute)');
        $form = $crawler->filter('#community-fields-form')->form();
        // The options come from the autocomplete: the page only holds the selected ones
        $form['community_fields[replaces][value]']->disableValidation()->setValue([(string) $remy->id?->toString()]);
        $this->client->submit($form);
        self::assertResponseRedirects($editUrl);

        $this->client->followRedirect();
        self::assertSelectorTextContains('tr[data-field="replaces"]', 'Saint Remy des trois rivières');

        $searchHelper->refresh(SearchIndex::PARISH);
        $crawler = $this->client->request('GET', '/admin/parishes', ['q' => 'remy']);
        $row = $crawler->filter('table.datagrid tbody tr');
        self::assertCount(1, $row);
        self::assertSelectorTextContains('td.state', 'Supprimée');
        self::assertSelectorTextContains('td.state .replaced-by', 'Remplacée par Paroisse Saint-Joseph du Haillon');
        self::assertSame("/admin/parishes/{$this->parish->id}/edit?q=remy", $row->filter('.replaced-by a')->attr('href'));

        $this->client->request('GET', "/admin/parishes/{$remy->id}/edit");
        self::assertSelectorTextContains('.alert.replaced-by', 'Cette paroisse est remplacée par Paroisse Saint-Joseph du Haillon');
        self::assertSelectorTextContains('tr[data-field="state"]', 'deleted');
        self::assertSelectorTextContains('tr[data-field="deletionReason"]', 'dissolved');

        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'parish', 'query' => 'remy']);
        self::assertSame([], json_decode((string) $this->client->getResponse()->getContent(), true)['results'], 'A deleted parish is no longer offered');

        // The replaced parish is brought back when the link is removed
        $form = $this->client->request('GET', $editUrl)->filter('#community-fields-form')->form();
        $form['community_fields[replaces][value]']->disableValidation()->setValue([]);
        $this->client->submit($form);
        self::assertResponseRedirects($editUrl);

        $searchHelper->refresh(SearchIndex::PARISH);
        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'parish', 'query' => 'remy']);
        self::assertCount(1, json_decode((string) $this->client->getResponse()->getContent(), true)['results']);
        $this->client->request('GET', "/admin/parishes/{$remy->id}/edit");
        self::assertSelectorNotExists('.alert.replaced-by');
    }

    public function testResettingTheReplacesFieldBringsTheParishesBack(): void
    {
        $remy = $this->createCommunity(CommunityType::PARISH, 'Saint Remy des trois rivières', $this->diocese);
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";
        $form = $this->client->request('GET', $editUrl)->filter('#community-fields-form')->form();
        $form['community_fields[replaces][value]']->disableValidation()->setValue([(string) $remy->id?->toString()]);
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        self::assertStringContainsString('Les paroisses remplacées seront réactivées', (string) $crawler->filter('tr[data-field="replaces"] .action-reset')->attr('onclick'));

        $this->client->submit($crawler->filter('form#reset-replaces')->form());
        self::assertResponseRedirects($editUrl);

        $this->client->request('GET', "/admin/parishes/{$remy->id}/edit");
        self::assertSelectorNotExists('.alert.replaced-by');
        self::assertSelectorTextContains('tr[data-field="state"]', 'Aucune valeur');
    }

    public function testADeletedParishCannotBeReplacedAgain(): void
    {
        $merged = $this->createCommunity(CommunityType::PARISH, 'Saint Vigor au pays athisien', $this->diocese, [
            $this->scraperField(FieldCommunity::STATE, CommunityState::DELETED->value),
            $this->scraperField(FieldCommunity::DELETION_REASON, CommunityDeletionReason::DUPLICATE->value),
        ]);
        $this->client->loginUser($this->adminUser, 'admin');
        $form = $this->client->request('GET', "/admin/parishes/{$this->parish->id}/edit")->filter('#community-fields-form')->form();
        // A page opened before the parish was deleted, for instance
        $form['community_fields[replaces][value]']->disableValidation()->setValue([(string) $merged->id?->toString()]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#community-fields-form', '« Remplace » : « Saint Vigor au pays athisien » est déjà supprimée.');
        self::assertSame([], $this->storedValues(FieldCommunity::REPLACES));
    }

    public function testParishesCanBeReplacedAtCreation(): void
    {
        $remy = $this->createCommunity(CommunityType::PARISH, 'Saint Remy des trois rivières', $this->diocese);
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes/new');
        self::assertSelectorTextContains('#community-fields-form', 'Les paroisses sélectionnées sont supprimées (motif : dissoute)');

        $form = $crawler->filter('#community-fields-form')->form(['community_fields[name][value]' => 'Paroisse Saint Joseph en Bocage']);
        $form['community_fields[replaces][value]']->disableValidation()->setValue([(string) $remy->id?->toString()]);
        $this->client->submit($form);
        self::assertMatchesRegularExpression('#^/admin/parishes/[0-9a-f-]{36}/edit$#', (string) $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', "/admin/parishes/{$remy->id}/edit");
        self::assertSelectorTextContains('.alert.replaced-by', 'Cette paroisse est remplacée par Paroisse Saint Joseph en Bocage');
    }

    public function testMainFormCsrfProtection(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $this->client->request('POST', "/admin/parishes/{$this->parish->id}/edit", [
            'community_fields' => ['contactCity' => ['value' => 'Nantes'], '_token' => 'csrf-token'],
        ], server: ['HTTP_ORIGIN' => 'https://attacker.example']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->storedValues(FieldCommunity::CONTACT_CITY));
    }

    public function testOnlyParishesCanBeEdited(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $this->client->request('GET', "/admin/parishes/{$this->diocese->id}/edit");

        self::assertResponseStatusCodeSame(404);
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 20; ++$i) {
            $this->createCommunity(CommunityType::PARISH, sprintf('Paroisse numéro %02d', $i), $this->diocese);
        }
        self::getContainer()->get(SearchHelperInterface::class)->refresh(SearchIndex::PARISH);
        $this->client->loginUser($this->adminUser, 'admin');

        $crawler = $this->client->request('GET', '/admin/parishes', ['diocese' => $this->diocese->id?->toString(), 'page' => 2]);
        self::assertCount(1, $crawler->filter('table.datagrid tbody tr'));
        self::assertSelectorTextSame('.results-count', '21 paroisse(s) trouvée(s).');

        // An invalid page number shows the first page
        $crawler = $this->client->request('GET', '/admin/parishes', ['diocese' => $this->diocese->id?->toString(), 'page' => 'abc']);
        self::assertResponseIsSuccessful();
        self::assertCount(20, $crawler->filter('table.datagrid tbody tr'));

        // A page beyond the last one shows the last one
        $crawler = $this->client->request('GET', '/admin/parishes', ['diocese' => $this->diocese->id?->toString(), 'page' => 99]);
        self::assertCount(1, $crawler->filter('table.datagrid tbody tr'));
    }

    public function testLogout(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', '/admin/parishes');
        $logoutLink = $crawler->filter('a[href^="/admin/logout"]');
        self::assertStringContainsString('_csrf_token=', (string) $logoutLink->attr('href'));

        $this->client->click($logoutLink->link());
        $this->client->request('GET', '/admin/parishes');
        self::assertResponseRedirects('http://localhost/admin/login');
    }

    public function testCountryCodeIsPickedFromTheCountryList(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";
        $crawler = $this->client->request('GET', $editUrl);
        self::assertSelectorTextContains('select[name="community_fields[contactCountryCode][value]"] option[value="FR"]', 'France');

        $this->client->submit($crawler->filter('#community-fields-form')->form(['community_fields[contactCountryCode][value]' => 'FR']));
        self::assertResponseRedirects($editUrl);
    }

    public function testTimezoneIsPickedFromTheTimezoneList(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $editUrl = "/admin/parishes/{$this->parish->id}/edit";
        $crawler = $this->client->request('GET', $editUrl);
        self::assertSelectorExists('select[name="community_fields[timezone][value]"] option[value="America/Martinique"]');

        $this->client->submit($crawler->filter('#community-fields-form')->form(['community_fields[timezone][value]' => 'America/Martinique']));
        self::assertResponseRedirects($editUrl);
        self::assertSame(['America/Martinique'], $this->storedValues(FieldCommunity::TIMEZONE));
    }

    public function testSearchUnavailable(): void
    {
        $searchService = $this->createStub(SearchServiceInterface::class);
        $searchService->method('searchParishes')->willThrowException(new ClientResponseException('Elasticsearch is down'));
        $searchService->method('searchDioceseIds')->willThrowException(new ClientResponseException('Elasticsearch is down'));
        $this->client->disableReboot();
        self::getContainer()->set(SearchServiceInterface::class, $searchService);
        $this->client->loginUser($this->adminUser, 'admin');

        $this->client->request('GET', '/admin/parishes', ['q' => 'joseph']);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'La recherche est momentanément indisponible.');

        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'diocese', 'query' => 'nantes']);
        self::assertResponseStatusCodeSame(503);
    }

    public function testLoginThrottling(): void
    {
        // The attempts are counted in a persistent cache: start from, and leave, a clean state
        $rateLimiterCache = self::getContainer()->get('cache.rate_limiter');
        $rateLimiterCache->clear();

        try {
            for ($i = 0; $i < 5; ++$i) {
                $this->client->request('GET', '/admin/login');
                $this->client->submitForm('Se connecter', ['_username' => 'admin@openchurch.test', '_password' => 'wrong password']);
            }

            // Even the right password is refused once the attempts are exhausted
            $this->client->request('GET', '/admin/login');
            $this->client->submitForm('Se connecter', ['_username' => 'admin@openchurch.test', '_password' => DummyAdminUserFactory::PASSWORD]);
            $this->client->followRedirect();
            self::assertSelectorTextContains('.alert-danger', 'Trop de tentatives de connexion échouées');
        } finally {
            $rateLimiterCache->clear();
        }
    }

    public function testRejectedEditDisplaysTheErrors(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $crawler = $this->client->request('GET', "/admin/parishes/{$this->parish->id}/edit");

        $form = $crawler->filter('#community-fields-form')->form([
            'community_fields[contactCity][value]' => 'Nantes',
            'community_fields[state][value]' => 'deleted',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.alert-danger', 'Rien n\'a été enregistré.');
        self::assertSelectorTextContains('tr[data-field="deletionReason"]', 'Un motif de suppression est obligatoire pour une paroisse supprimée.');
        self::assertSelectorTextContains('tr[data-field="contactCity"]', 'Aucune valeur');
        self::assertInputValueSame('community_fields[contactCity][value]', 'Nantes', 'The input of the admin is kept');
        self::assertSame([], $this->storedValues(FieldCommunity::CONTACT_CITY));
    }

    public function testResetRequiresAValidCsrfToken(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $this->client->request('POST', "/admin/parishes/{$this->parish->id}/fields/contactEmail/reset", ['_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUnknownCommunity(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');
        $this->client->request('GET', '/admin/parishes/'.Uuid::v7().'/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAutocomplete(): void
    {
        $this->client->loginUser($this->adminUser, 'admin');

        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'diocese', 'query' => 'nantes']);
        self::assertResponseIsSuccessful();
        self::assertSame(
            ['results' => [['entityId' => $this->diocese->id?->toString(), 'entityAsString' => 'Diocèse de Nantes']], 'next_page' => null],
            json_decode((string) $this->client->getResponse()->getContent(), true),
        );

        $this->client->request('GET', '/admin/parishes/search-communities', ['type' => 'parish', 'query' => 'blandine']);
        self::assertSame(
            ['results' => [['entityId' => $this->otherParish->id?->toString(), 'entityAsString' => 'Paroisse Sainte-Blandine — Diocèse de Lyon']], 'next_page' => null],
            json_decode((string) $this->client->getResponse()->getContent(), true),
        );
    }

    private function communityCount(): int
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(Community::class)->count([]);
    }

    /**
     * @param list<Field> $extraFields
     */
    private function createCommunity(CommunityType $type, string $name, ?Community $parent = null, array $extraFields = []): Community
    {
        $fields = [
            $this->scraperField(FieldCommunity::TYPE, $type->value),
            $this->scraperField(FieldCommunity::NAME, $name),
            $this->scraperField(FieldCommunity::WIKIDATA_ID, random_int(1, 1_000_000_000)),
            ...$extraFields,
        ];
        if (null !== $parent) {
            $fields[] = $this->scraperField(FieldCommunity::PARENT_COMMUNITY_ID, $parent);
        }

        return DummyCommunityFactory::createOne(['fields' => $fields]);
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

    /**
     * The values the parish has for this field, as stored in the database.
     *
     * @return list<mixed>
     */
    private function storedValues(FieldCommunity $name): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $parish = $em->find(Community::class, $this->parish->id);
        self::assertInstanceOf(Community::class, $parish);

        return array_values(array_map(
            static fn (Field $field): mixed => $field->getValue(),
            $parish->getFieldsByName($name)->toArray(),
        ));
    }
}
