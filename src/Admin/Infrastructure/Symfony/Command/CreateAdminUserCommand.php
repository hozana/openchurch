<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Symfony\Command;

use App\Admin\Domain\Model\AdminUser;
use App\Admin\Domain\Repository\AdminUserRepositoryInterface;
use App\Agent\Domain\Repository\AgentRepositoryInterface;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('app:admin:create-user', 'Creates an admin backend user, or resets the password of an existing one')]
final readonly class CreateAdminUserCommand
{
    private const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private AdminUserRepositoryInterface $adminUserRepo,
        private AgentRepositoryInterface $agentRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Email address used to log in')] string $email,
    ): int {
        if (count($this->validator->validate($email, new Email())) > 0) {
            $io->error(sprintf('"%s" is not a valid email address.', $email));

            return Command::INVALID;
        }

        $adminUser = $this->adminUserRepo->ofEmail($email);
        $agent = $this->agentRepo->ofName(AdminUser::AGENT_NAME);
        if (null === $agent) {
            $io->error(sprintf('The %s agent is missing: run the database migrations.', AdminUser::AGENT_NAME));

            return Command::FAILURE;
        }

        $password = $io->askHidden(sprintf('Password (%d characters minimum)', self::MIN_PASSWORD_LENGTH));
        if (!is_string($password) || mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $io->error(sprintf('The password must be at least %d characters long.', self::MIN_PASSWORD_LENGTH));

            return Command::INVALID;
        }

        $isNew = null === $adminUser;
        $adminUser ??= new AdminUser($email, '', $agent);
        $adminUser->password = $this->passwordHasher->hashPassword($adminUser, $password);

        $this->transactionManager->transactional(function () use ($adminUser): void {
            $this->adminUserRepo->add($adminUser);
        });

        $io->success(sprintf($isNew ? 'Admin user %s created.' : 'Password of admin user %s changed.', $email));

        return Command::SUCCESS;
    }
}
