<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Doctrine;

use App\Admin\Domain\Model\AdminUser;
use App\Admin\Domain\Repository\AdminUserRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends DoctrineRepository<AdminUser>
 */
final class DoctrineAdminUserRepository extends DoctrineRepository implements AdminUserRepositoryInterface
{
    private const string ENTITY_CLASS = AdminUser::class;

    private const string ALIAS = 'adminUser';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function ofEmail(string $email): ?AdminUser
    {
        return $this->em->getRepository(self::ENTITY_CLASS)->findOneBy(['email' => $email]);
    }

    public function add(AdminUser $adminUser): void
    {
        $this->em->persist($adminUser);
    }
}
