<?php

declare(strict_types=1);

namespace App\Admin\Domain\Repository;

use App\Admin\Domain\Model\AdminUser;
use App\Shared\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<AdminUser>
 */
interface AdminUserRepositoryInterface extends RepositoryInterface
{
    public function ofEmail(string $email): ?AdminUser;

    public function add(AdminUser $adminUser): void;
}
