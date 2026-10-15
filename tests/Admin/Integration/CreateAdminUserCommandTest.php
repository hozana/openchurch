<?php

declare(strict_types=1);

namespace App\Tests\Admin\Integration;

use App\Admin\Domain\Model\AdminUser;
use App\Admin\Domain\Repository\AdminUserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminUserCommandTest extends KernelTestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->tester = new CommandTester(new Application(self::bootKernel())->find('app:admin:create-user'));
    }

    public function testCreatesThenChangesThePassword(): void
    {
        $this->tester->setInputs(['a first long password']);
        self::assertSame(Command::SUCCESS, $this->tester->execute(['email' => 'admin@openchurch.test']));
        self::assertStringContainsString('Admin user admin@openchurch.test created.', $this->tester->getDisplay());
        self::assertTrue($this->isPasswordValid('a first long password'));
        $agent = $this->adminUser()->agent;
        self::assertSame(AdminUser::AGENT_NAME, $agent->name, 'The admin user is linked to the agent of the admins');

        $this->tester->setInputs(['a second long password']);
        self::assertSame(Command::SUCCESS, $this->tester->execute(['email' => 'admin@openchurch.test']));
        self::assertStringContainsString('Password of admin user admin@openchurch.test changed.', $this->tester->getDisplay());
        self::assertTrue($this->isPasswordValid('a second long password'));
        self::assertFalse($this->isPasswordValid('a first long password'));
        self::assertTrue($this->adminUser()->agent->is($agent), 'The admin user keeps its agent');

        $this->tester->setInputs(['another long password']);
        self::assertSame(Command::SUCCESS, $this->tester->execute(['email' => 'other.admin@openchurch.test']));
        $otherAdmin = self::getContainer()->get(AdminUserRepositoryInterface::class)->ofEmail('other.admin@openchurch.test');
        self::assertInstanceOf(AdminUser::class, $otherAdmin);
        self::assertTrue($otherAdmin->agent->is($agent), 'All the admin users share the same agent');
    }

    public function testRejectsInvalidInput(): void
    {
        self::assertSame(Command::INVALID, $this->tester->execute(['email' => 'not an email']));

        $this->tester->setInputs(['short']);
        self::assertSame(Command::INVALID, $this->tester->execute(['email' => 'admin@openchurch.test']));
        self::assertNull(self::getContainer()->get(AdminUserRepositoryInterface::class)->ofEmail('admin@openchurch.test'));
    }

    private function isPasswordValid(string $password): bool
    {
        return self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($this->adminUser(), $password);
    }

    private function adminUser(): AdminUser
    {
        $adminUser = self::getContainer()->get(AdminUserRepositoryInterface::class)->ofEmail('admin@openchurch.test');
        self::assertInstanceOf(AdminUser::class, $adminUser);

        return $adminUser;
    }
}
