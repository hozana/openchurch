<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin\Controller;

use App\Admin\Application\AdminCommunityFields;
use App\Admin\Application\AdminFieldInput;
use App\Admin\Application\CommunityFieldsEditor;
use App\Admin\Application\CommunityFieldsOverview;
use App\Admin\Application\CommunityFieldsRejectedException;
use App\Admin\Application\CommunityLabeler;
use App\Admin\Application\FieldOverview;
use App\Admin\Application\ParishSearch;
use App\Admin\Application\ParishSearchPage;
use App\Admin\Domain\Model\AdminUser;
use App\Admin\Infrastructure\Symfony\Form\CommunityFieldsType;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\FieldValueNormalizer;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Transport\Exception\TransportException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_ADMIN')]
#[AdminRoute('/parishes', name: 'parish')]
final class ParishController extends AbstractController
{
    private const string RESET_CSRF_TOKEN_ID = 'admin-reset-field';

    private const int AUTOCOMPLETE_SIZE = 20;

    public function __construct(
        private readonly ParishSearch $parishSearch,
        private readonly CommunityRepositoryInterface $communityRepo,
        private readonly CommunityFieldsOverview $fieldsOverview,
        private readonly CommunityFieldsEditor $fieldsEditor,
        private readonly CommunityLabeler $communityLabeler,
        private readonly SearchServiceInterface $searchService,
    ) {
    }

    #[AdminRoute('/', name: 'index', options: ['methods' => ['GET']])]
    public function index(
        #[MapQueryParameter] string $q = '',
        #[MapQueryParameter] string $diocese = '',
        #[MapQueryParameter(flags: FILTER_NULL_ON_FAILURE)] ?int $page = null,
    ): Response {
        // Search documents hold ids in their canonical (lowercase) form
        $dioceseId = Uuid::isValid($diocese) ? Uuid::fromString($diocese)->toRfc4122() : null;

        try {
            $result = $this->parishSearch->search($q, $dioceseId, $page ?? 1);
        } catch (ElasticsearchException|TransportException) {
            $this->flash('danger', 'La recherche est momentanément indisponible.');
            $result = new ParishSearchPage([], 0, 1, 1);
        }

        return $this->render('@admin/parish/index.html.twig', [
            'result' => $result,
            'query' => $q,
            'dioceseId' => $dioceseId,
            'dioceseLabel' => null !== $dioceseId ? $this->communityLabeler->labels([$dioceseId])[$dioceseId] ?? null : null,
        ]);
    }

    #[AdminRoute('/{id}/edit', name: 'edit', options: ['methods' => ['GET', 'POST'], 'requirements' => ['id' => Requirement::UUID]])]
    public function edit(Request $request, string $id): Response
    {
        $parish = $this->findParish($id);
        $fields = $this->fieldsOverview->build($parish, $this->adminUser()->agent);
        $form = $this->createFieldsForm(self::formData($fields));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $changes = $this->fieldsEditor->apply($parish, $this->adminUser(), self::toInputs($form));
                $this->flash(0 === $changes ? 'info' : 'success', 0 === $changes ? 'Aucune modification.' : sprintf('%d champ(s) enregistré(s).', $changes));

                return $this->redirectToRoute('admin_parish_edit', ['id' => $id] + self::listState($request));
            } catch (CommunityFieldsRejectedException $e) {
                foreach ($e->errors as $error) {
                    $form->addError(new FormError($error));
                }
                // The rejected changes were discarded: display the stored values next to the submitted ones
                $parish = $this->findParish($id);
                $fields = $this->fieldsOverview->build($parish, $this->adminUser()->agent);
            }
        }

        return $this->render('@admin/parish/edit.html.twig', [
            'parish' => $parish,
            'parishName' => CommunityLabeler::name($parish),
            'fields' => $fields,
            'form' => $form->createView(),
            'listState' => self::listState($request),
            'resetCsrfTokenId' => self::RESET_CSRF_TOKEN_ID,
        ], new Response(status: $form->getErrors(true)->count() > 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[AdminRoute('/{id}/fields/{name}/reset', name: 'reset_field', options: ['methods' => ['POST'], 'requirements' => ['id' => Requirement::UUID]])]
    public function resetField(Request $request, string $id, string $name): Response
    {
        if (!$this->isCsrfTokenValid(self::RESET_CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $fieldName = FieldCommunity::tryFrom($name);
        if (null === $fieldName || !AdminCommunityFields::isEditable($fieldName)) {
            throw $this->createNotFoundException(sprintf('Field %s cannot be edited from the admin.', $name));
        }

        $label = AdminCommunityFields::label($fieldName);
        try {
            $changes = $this->fieldsEditor->apply($this->findParish($id), $this->adminUser(), [new AdminFieldInput($fieldName, null)]);
            if (0 === $changes) {
                $this->flash('info', sprintf('Le champ « %s » n\'avait pas de valeur admin.', $label));
            } else {
                $this->flash('success', sprintf('La valeur admin du champ « %s » a été supprimée.', $label));
            }
        } catch (CommunityFieldsRejectedException $e) {
            $this->flash('danger', implode(' ', $e->errors));
        }

        return $this->redirectToRoute('admin_parish_edit', ['id' => $id] + self::listState($request));
    }

    /**
     * Autocomplete endpoint, in the format expected by the EasyAdmin autocomplete widget.
     */
    #[AdminRoute('/search-communities', name: 'search_communities', options: ['methods' => ['GET']])]
    public function searchCommunities(
        #[MapQueryParameter] string $type = 'parish',
        #[MapQueryParameter] string $query = '',
    ): JsonResponse {
        $isDiocese = 'diocese' === $type;
        try {
            $ids = $isDiocese
                ? $this->searchService->searchDioceseIds($query, self::AUTOCOMPLETE_SIZE, 0)
                : $this->searchService->searchParishIds($query, null, self::AUTOCOMPLETE_SIZE, 0);
        } catch (ElasticsearchException|TransportException) {
            return new JsonResponse(['results' => [], 'next_page' => null], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $results = [];
        foreach ($this->communityLabeler->labels(array_values($ids), withParent: !$isDiocese) as $communityId => $label) {
            $results[] = ['entityId' => $communityId, 'entityAsString' => $label];
        }

        return new JsonResponse(['results' => $results, 'next_page' => null]);
    }

    /**
     * EasyAdmin renders flash messages as HTML: they must be escaped, since they can contain community names.
     */
    private function flash(string $type, string $message): void
    {
        $this->addFlash($type, htmlspecialchars($message, ENT_QUOTES));
    }

    /**
     * @param array<string, array{value: string|int|float|list<string>|null, explanation: ?string}> $data
     */
    private function createFieldsForm(array $data): FormInterface
    {
        return $this->createForm(CommunityFieldsType::class, $data, [
            'diocese_autocomplete_url' => $this->generateUrl('admin_parish_search_communities', ['type' => 'diocese']),
            'parish_autocomplete_url' => $this->generateUrl('admin_parish_search_communities', ['type' => 'parish']),
        ]);
    }

    private function findParish(string $id): Community
    {
        $community = $this->communityRepo->ofId(Uuid::fromString($id));
        if (null === $community || CommunityType::PARISH->value !== $community->getMostTrustableFieldByName(FieldCommunity::TYPE)?->getValue()) {
            throw $this->createNotFoundException(sprintf('Parish %s not found.', $id));
        }

        return $community;
    }

    private function adminUser(): AdminUser
    {
        $user = $this->getUser();
        if (!$user instanceof AdminUser) {
            throw new LogicException('The admin backend is only available to admin users.');
        }

        return $user;
    }

    /**
     * @return array<string, string> the non-empty search parameters of the parish list
     */
    private static function listState(Request $request): array
    {
        return array_filter([
            'q' => $request->query->getString('q'),
            'diocese' => $request->query->getString('diocese'),
            'page' => $request->query->getString('page'),
        ], static fn (string $value): bool => '' !== $value);
    }

    /**
     * The edit form data: the values of the logged-in admin.
     *
     * @param list<FieldOverview> $fields
     *
     * @return array<string, array{value: string|int|float|list<string>|null, explanation: ?string}>
     */
    private static function formData(array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if ($field->editable) {
                $data[$field->name->value] = [
                    'value' => $field->adminState->value,
                    'explanation' => $field->adminState->explanation,
                ];
            }
        }

        return $data;
    }

    /**
     * @return list<AdminFieldInput>
     */
    private static function toInputs(FormInterface $form): array
    {
        $inputs = [];
        foreach (AdminCommunityFields::editable() as $name) {
            $fieldForm = $form->get($name->value);
            $explanation = $fieldForm->get('explanation')->getData();

            $inputs[] = new AdminFieldInput(
                $name,
                FieldValueNormalizer::normalize($fieldForm->get('value')->getData()),
                is_string($explanation) && '' !== $explanation ? $explanation : null,
            );
        }

        return $inputs;
    }
}
