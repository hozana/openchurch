<?php

declare(strict_types=1);

namespace App\Admin\Application;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Admin\Domain\Model\AdminUser;
use App\Agent\Domain\Model\Agent;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Writes the values admins give to a parish's fields, on behalf of the agent they share.
 */
final readonly class CommunityFieldsEditor
{
    public const string SOURCE = 'admin';

    public function __construct(
        private FieldService $fieldService,
        private CommunityLabeler $communityLabeler,
        private FieldRepositoryInterface $fieldRepo,
        private CommunityRepositoryInterface $communityRepo,
        private ParishReplacements $replacements,
        private ValidatorInterface $validator,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Creates a parish, whose fields are written like those of an edit. Its type is not an input, since
     * admins can't change it: it is always a parish. The parishes it replaces are deleted along with it.
     *
     * @param list<AdminFieldInput> $inputs
     *
     * @throws CommunityFieldsRejectedException
     */
    public function create(AdminUser $adminUser, array $inputs): Community
    {
        $agent = $adminUser->agent;
        $parish = new Community();
        $replacement = $this->replacements->plan($parish, $agent, $inputs);
        $this->ensureApplicable($parish, $agent, $inputs, [...self::missingNameErrors($inputs), ...$this->replacements->errors($replacement)]);
        $this->communityRepo->add($parish);

        [$payloads] = $this->prepareChanges($parish, $agent, $inputs);
        $typePayload = new AdminFieldInput(FieldCommunity::TYPE, CommunityType::PARISH->value)->toPayload(self::SOURCE);
        $this->write($parish, $agent, [$typePayload, ...$payloads], [], $replacement);

        return $parish;
    }

    /**
     * The parishes newly designated in « replaces » are deleted, and the ones removed from it are brought back.
     *
     * @param list<AdminFieldInput> $inputs
     *
     * @return int the number of changed fields
     *
     * @throws CommunityFieldsRejectedException
     */
    public function apply(Community $parish, AdminUser $adminUser, array $inputs): int
    {
        $agent = $adminUser->agent;
        $replacement = $this->replacements->plan($parish, $agent, $inputs);
        $this->ensureApplicable($parish, $agent, $inputs, $this->replacements->errors($replacement));

        $initialViolations = $this->violationMessages($parish);
        [$payloads, $changes] = $this->prepareChanges($parish, $agent, $inputs);
        if (0 === $changes) {
            return 0;
        }

        $this->write($parish, $agent, $payloads, $initialViolations, $replacement);

        return $changes;
    }

    /**
     * Removes the admin values set to null, and turns the other changed values into payloads for FieldService.
     *
     * @param list<AdminFieldInput> $inputs
     *
     * @return array{list<Field>, int} the payloads, and the number of changed fields
     */
    private function prepareChanges(Community $parish, Agent $agent, array $inputs): array
    {
        $payloads = [];
        $changes = 0;

        foreach ($inputs as $input) {
            $adminField = $parish->getFieldByNameAndAgent($input->name, $agent);

            if (null === $input->value) {
                if (null !== $adminField) {
                    $this->removeField($parish, $adminField);
                    ++$changes;
                }

                continue;
            }

            if (null !== $adminField && AdminFieldState::of($adminField)->equals($input->state())) {
                continue;
            }

            $payloads[] = $input->toPayload(self::SOURCE);
            ++$changes;
        }

        return [$payloads, $changes];
    }

    /**
     * Applies the payloads, checks the community, and writes everything, or nothing if anything is rejected.
     *
     * @param list<Field>  $payloads
     * @param list<string> $initialViolations
     *
     * @throws CommunityFieldsRejectedException
     */
    private function write(Community $parish, Agent $agent, array $payloads, array $initialViolations, ParishReplacementPlan $replacement): void
    {
        try {
            $this->fieldService->upsertFields($parish, $payloads, $agent);
            $this->replacements->apply($parish, $agent, $replacement);
            $this->ensureNoNewViolation($parish, $initialViolations);
        } catch (CommunityFieldsRejectedException $e) {
            $this->discardChanges();

            throw $e;
        } catch (ValidationException $e) {
            $this->discardChanges();

            throw new CommunityFieldsRejectedException(self::messages($e->getConstraintViolationList()));
        } catch (ProblemExceptionInterface $e) {
            $this->discardChanges();

            throw new CommunityFieldsRejectedException([$e->getDetail() ?? $e->getTitle() ?? $e->getType()]);
        } catch (HttpExceptionInterface $e) {
            $this->discardChanges();

            throw new CommunityFieldsRejectedException([$e->getMessage()]);
        }

        $this->em->flush();
    }

    /**
     * Checks what can be checked before changing anything: nothing has to be undone on failure.
     *
     * @param list<AdminFieldInput> $inputs
     * @param list<string>          $errors the errors already found, reported along with the ones of the inputs
     *
     * @throws CommunityFieldsRejectedException
     */
    private function ensureApplicable(Community $parish, Agent $agent, array $inputs, array $errors = []): void
    {
        foreach ($inputs as $input) {
            if (!AdminCommunityFields::isEditable($input->name)) {
                throw new LogicException(sprintf('Field %s cannot be edited from the admin.', $input->name->value));
            }

            $label = AdminCommunityFields::label($input->name);
            if (null === $input->value && null !== $input->explanation && null === $parish->getFieldByNameAndAgent($input->name, $agent)) {
                $errors[] = sprintf('« %s » : une explication ne peut accompagner qu\'une valeur.', $label);
            }

            $errors = [...$errors, ...$this->relationErrors($parish, $input)];
        }

        if ([] !== $errors) {
            throw new CommunityFieldsRejectedException($errors);
        }
    }

    /**
     * A parish without a name can't be told apart in the list, nor found by the search.
     *
     * @param list<AdminFieldInput> $inputs
     *
     * @return list<string>
     */
    private static function missingNameErrors(array $inputs): array
    {
        foreach ($inputs as $input) {
            if (FieldCommunity::NAME === $input->name && null !== $input->value) {
                return [];
            }
        }

        return [sprintf('« %s » : obligatoire pour créer une paroisse.', AdminCommunityFields::label(FieldCommunity::NAME))];
    }

    /**
     * The parent of a parish must be an existing diocese, and a parish only replaces other existing parishes.
     *
     * @return list<string>
     */
    private function relationErrors(Community $parish, AdminFieldInput $input): array
    {
        $expectedType = self::expectedRelatedType($input->name);
        if (null === $expectedType || null === $input->value) {
            return [];
        }

        $label = AdminCommunityFields::label($input->name);
        $ids = $input->relatedIds();
        if (in_array($parish->id?->toRfc4122(), $ids, true)) {
            return [sprintf('« %s » : une paroisse ne peut pas se désigner elle-même.', $label)];
        }

        $related = $this->communityLabeler->load($ids);
        if (count($related) !== count($ids)) {
            return [sprintf('« %s » : communauté introuvable.', $label)];
        }

        $errors = [];
        foreach ($related as $community) {
            if ($expectedType->value !== $community->getMostTrustableFieldByName(FieldCommunity::TYPE)?->getValue()) {
                $errors[] = sprintf('« %s » : « %s » n\'est pas %s.', $label, CommunityLabeler::label($community), self::typeName($expectedType));
            }
        }

        return $errors;
    }

    /**
     * The type of the communities a relation field points to, or null if the field is not a relation.
     */
    private static function expectedRelatedType(FieldCommunity $name): ?CommunityType
    {
        return match ($name) {
            FieldCommunity::PARENT_COMMUNITY_ID => CommunityType::DIOCESE,
            FieldCommunity::REPLACES => CommunityType::PARISH,
            default => null,
        };
    }

    private static function typeName(CommunityType $type): string
    {
        return CommunityType::DIOCESE === $type ? 'un diocèse' : 'une paroisse';
    }

    private function removeField(Community $parish, Field $field): void
    {
        // Leave $field->community set: the search index listener needs it to reindex the parish
        $parish->fields->removeElement($field);
        $this->fieldRepo->remove($field);
    }

    /**
     * @param list<string> $initialViolations
     */
    private function ensureNoNewViolation(Community $parish, array $initialViolations): void
    {
        $newViolations = [];
        foreach ($this->violationMessages($parish) as $violation) {
            // Compare occurrences: an admin value can break a constraint the same way another agent's does
            $key = array_search($violation, $initialViolations, true);
            if (false === $key) {
                $newViolations[] = $violation;
            } else {
                unset($initialViolations[$key]);
            }
        }

        if ([] !== $newViolations) {
            throw new CommunityFieldsRejectedException($newViolations);
        }
    }

    /**
     * @return list<string>
     */
    private function violationMessages(Community $parish): array
    {
        return self::messages($this->validator->validate($parish));
    }

    /**
     * Forgets the changes FieldService already made in memory, so that no later flush can write them.
     * Every entity of the request gets detached (the logged-in admin included): callers must reload
     * what they display afterwards.
     */
    private function discardChanges(): void
    {
        $this->em->clear();
    }

    /**
     * @return list<string>
     */
    private static function messages(ConstraintViolationListInterface $violations): array
    {
        return array_values(array_map(
            static fn (ConstraintViolationInterface $violation): string => (string) $violation->getMessage(),
            iterator_to_array($violations),
        ));
    }
}
