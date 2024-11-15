<?php

declare(strict_types=1);

namespace App\Field\Domain\Repository;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends RepositoryInterface<Field>
 */
interface FieldRepositoryInterface extends RepositoryInterface
{
    public function add(Field $field): void;

    public function exists(Uuid $id, FieldPlace|FieldCommunity $fieldName, mixed $fieldValue): string|null;
}