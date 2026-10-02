<?php

declare(strict_types=1);

namespace App\Admin\Domain\Model;

use App\Agent\Domain\Model\Agent;
use DateTimeImmutable;
use Deprecated;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[ORM\Entity]
#[ORM\Table]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface, Stringable
{
    /** The agent shared by all admin users, created by the Version20260930151602 migration */
    public const string AGENT_NAME = 'OPENCHURCH_ADMIN';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Column(unique: true)]
        public string $email,

        /** Hashed password */
        #[ORM\Column]
        public string $password,

        /** The agent the fields edited by this admin are written on behalf of (see AGENT_NAME) */
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        public Agent $agent,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => hash('crc32c', $this->password),
            'createdAt' => $this->createdAt,
        ];
    }

    /**
     * Required by UserInterface until Symfony 8; the attribute tells Symfony not to call it.
     */
    #[Deprecated('No plain-text credential is ever held by this user.')]
    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        Assert::stringNotEmpty($this->email);

        return $this->email;
    }
}
