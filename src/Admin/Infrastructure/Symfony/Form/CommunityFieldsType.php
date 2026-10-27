<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Symfony\Form;

use App\Admin\Application\AdminCommunityFields;
use App\Admin\Application\CommunityLabeler;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityState;
use BackedEnum;
use Doctrine\DBAL\Types\Types;
use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CommunityFieldsType extends AbstractType
{
    private const array CHOICE_LABELS = [
        'active' => 'Active',
        'deleted' => 'Supprimée',
        'garbage' => 'Donnée erronée',
        'duplicate' => 'Doublon',
        'dissolved' => 'Dissoute',
    ];

    /**
     * Each field is a row holding two inner rows (the value and its explanation). EasyAdmin spaces rows with a large
     * bottom padding: tighten the inner ones, which would otherwise stack three of them per field. The explanation,
     * last of the row, has none.
     */
    private const array INNER_ROW_ATTR = ['class' => 'pb-2'];

    private const array LAST_INNER_ROW_ATTR = ['class' => 'pb-0'];

    private const string REPLACES_HELP = 'Les paroisses sélectionnées sont supprimées (motif : dissoute) à l\'enregistrement. Retirer une paroisse de la liste la réactive.';

    public function __construct(
        private readonly CommunityLabeler $communityLabeler,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (AdminCommunityFields::editable() as $name) {
            $required = $options['require_name'] && FieldCommunity::NAME === $name;
            $field = $builder->create($name->value, FormType::class, [
                'label' => AdminCommunityFields::label($name),
                // The row has no input of its own: this only drives the required marker of its label
                'required' => $required,
            ]);

            if (self::isRelation($name)) {
                // The selectable communities come from the autocomplete: only the selected ones are known, on
                // display as well as on submit
                $addChoice = fn (FormEvent $event) => $this->addCommunityChoice($event->getForm(), $name, $event->getData(), $options);
                $field->addEventListener(FormEvents::PRE_SET_DATA, $addChoice);
                $field->addEventListener(FormEvents::PRE_SUBMIT, $addChoice);
            } else {
                $field->add('value', ...self::valueType($name, $required));
            }

            $field->add('explanation', TextType::class, [
                'label' => false,
                'required' => false,
                'row_attr' => self::LAST_INNER_ROW_ATTR,
                'attr' => ['placeholder' => 'Source ou justification (publique, optionnelle)'],
            ]);
            $builder->add($field);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, self::requireDeletionReason(...));
    }

    private static function requireDeletionReason(FormEvent $event): void
    {
        $form = $event->getForm();
        $state = $form->get(FieldCommunity::STATE->value)->get('value')->getData();
        $reason = $form->get(FieldCommunity::DELETION_REASON->value)->get('value');
        if (CommunityState::DELETED->value === $state && null === $reason->getData()) {
            $reason->addError(new FormError('Un motif de suppression est obligatoire pour une paroisse supprimée.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['diocese_autocomplete_url', 'parish_autocomplete_url']);
        $resolver->setAllowedTypes('diocese_autocomplete_url', 'string');
        $resolver->setAllowedTypes('parish_autocomplete_url', 'string');
        // Whether the name must be given, as when creating a parish
        $resolver->setDefault('require_name', false);
        $resolver->setAllowedTypes('require_name', 'bool');
    }

    private static function isRelation(FieldCommunity $name): bool
    {
        return in_array($name->getType(), ['Community', 'Community[]'], true);
    }

    /**
     * @return array{class-string<FormTypeInterface>, array<string, mixed>}
     */
    private static function valueType(FieldCommunity $name, bool $required): array
    {
        $common = ['label' => false, 'required' => $required, 'row_attr' => self::INNER_ROW_ATTR];
        $type = $name->getType();

        if (FieldCommunity::CONTACT_COUNTRY_CODE === $name) {
            return [CountryType::class, $common + ['placeholder' => '—']];
        }

        if (FieldCommunity::TIMEZONE === $name) {
            return [TimezoneType::class, $common + ['placeholder' => '—']];
        }

        if (Types::STRING === $type) {
            $constraints = [new Length(max: Field::STRING_MAX_LENGTH)];
            if ($required) {
                $constraints[] = new NotBlank(message: 'Ce champ est obligatoire pour créer une paroisse.');
            }
            if (FieldCommunity::CONTACT_EMAIL === $name) {
                $constraints[] = new Email();
            }

            return [TextType::class, $common + ['constraints' => $constraints]];
        }

        if (enum_exists($type) && is_subclass_of($type, BackedEnum::class)) {
            return [ChoiceType::class, $common + [
                'choices' => self::enumChoices($type),
                'placeholder' => '—',
            ]];
        }

        throw new LogicException(sprintf('No admin form type for field %s of type %s.', $name->value, $type));
    }

    /**
     * @param class-string<BackedEnum> $enumClass
     *
     * @return array<string, string|int>
     */
    private static function enumChoices(string $enumClass): array
    {
        $choices = [];
        foreach ($enumClass::cases() as $case) {
            $choices[self::CHOICE_LABELS[$case->value] ?? (string) $case->value] = $case->value;
        }

        return $choices;
    }

    /**
     * Adds (or replaces) the value of a community field, autocompleted through the search index.
     *
     * @param array<string, mixed> $options
     */
    private function addCommunityChoice(FormInterface $form, FieldCommunity $name, mixed $data, array $options): void
    {
        $multiple = 'Community[]' === $name->getType();
        $value = is_array($data) ? $data['value'] ?? null : null;
        $ids = array_values(array_filter(is_array($value) ? $value : [$value], is_string(...)));
        $labels = $this->communityLabeler->labels($ids, withParent: $multiple);

        $form->add('value', ChoiceType::class, [
            'label' => false,
            'required' => false,
            'row_attr' => self::INNER_ROW_ATTR,
            'help' => FieldCommunity::REPLACES === $name ? self::REPLACES_HELP : null,
            'multiple' => $multiple,
            'placeholder' => $multiple ? null : '—',
            'choice_loader' => new CommunityIdChoiceLoader(array_keys($labels)),
            'choice_label' => static fn (string $id): string => $labels[$id] ?? $id,
            'attr' => [
                'data-ea-widget' => 'ea-autocomplete',
                'data-ea-autocomplete-endpoint-url' => $options[$multiple ? 'parish_autocomplete_url' : 'diocese_autocomplete_url'],
            ],
        ]);
    }
}
