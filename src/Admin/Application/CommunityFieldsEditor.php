<?php

declare(strict_types=1);

namespace App\Admin\Application;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Admin\Domain\Model\AdminUser;
use App\Agent\Domain\Model\Agent;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Uid\Uuid;
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
        private ValidatorInterface $validator,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @param list<AdminFieldInput> $inputs
     *
     * @return int the number of changed fields
     *
     * @throws CommunityFieldsRejectedException
     */
    public function apply(Community $parish, AdminUser $adminUser, array $inputs): int
    {
        $agent = $adminUser->agent;
        $this->ensureApplicable($parish, $agent, $inputs);

        $initialViolations = $this->violationMessages($parish);
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

            $payloads[] = self::toPayload($input);
            ++$changes;
        }

        if (0 === $changes) {
            return 0;
        }

        try {
            $this->fieldService->upsertFields($parish, $payloads, $agent);
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

        return $changes;
    }

    /**
     * Checks what can be checked before changing anything: nothing has to be undone on failure.
     *
     * @param list<AdminFieldInput> $inputs
     *
     * @throws CommunityFieldsRejectedException
     */
    private function ensureApplicable(Community $parish, Agent $agent, array $inputs): void
    {
        $errors = [];
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
     * The parent of a parish must be an existing diocese, and a parish only replaces other existing parishes.
     *
     * @return list<string>
     */
    private function relationErrors(Community $parish, AdminFieldInput $input): array
    {
        $expectedType = match ($input->name) {
            FieldCommunity::PARENT_COMMUNITY_ID => CommunityType::DIOCESE,
            FieldCommunity::REPLACES => CommunityType::PARISH,
            default => null,
        };
        if (null === $expectedType || null === $input->value) {
            return [];
        }

        $label = AdminCommunityFields::label($input->name);
        $ids = array_values(array_unique(array_map(
            static fn (string $id): string => Uuid::isValid($id) ? Uuid::fromString($id)->toRfc4122() : $id,
            array_filter((array) $input->value, is_string(...)),
        )));
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
                $errors[] = sprintf('« %s » : « %s » n\'est pas un%s %s.', $label, CommunityLabeler::label($community), CommunityType::DIOCESE === $expectedType ? '' : 'e', CommunityType::DIOCESE === $expectedType ? 'diocèse' : 'paroisse');
            }
        }

        return $errors;
    }

    private static function toPayload(AdminFieldInput $input): Field
    {
        $payload = new Field();
        $payload->name = $input->name->value;
        $payload->value = $input->value;
        $payload->engine = FieldEngine::HUMAN;
        $payload->reliability = FieldReliability::HIGH;
        $payload->source = self::SOURCE;
        $payload->explanation = $input->explanation;

        return $payload;
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
