<?php

namespace App\FieldHolder\Application;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Field\Domain\Model\Field;
use Exception;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FieldHolderUpsertService
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function getFieldByName(array $fields, string $fieldName): mixed
    {
        $result = array_values(array_filter($fields, 
            fn (array $field) => $field['name'] === $fieldName
        ));

        return count($result) > 0 ? $result[0] : null;
    }

    public function handleError(object $entity, Exception $e, callable $detachCallback): string
    {
        foreach ($entity->fields as $field) {
            call_user_func($detachCallback, $field);
            call_user_func($detachCallback, $entity);
        }

        if ($e instanceof ValidationException) {
            return $e->getMessage();
        }
        if ($e instanceof ProblemExceptionInterface) {
            return $e->getDetail();
        }

        return $e->getMessage();
    }

    public function arrayToFields(array $fields) : array {
        return array_map(
            fn (array $field) => $this->denormalizer->denormalize($field, Field::class),
            $fields
        );
    }
}
