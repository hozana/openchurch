<?php

namespace App\Agent\Domain\Model;

use App\Agent\Infrastructure\Doctrine\DoctrineAgentRepository;
use App\Field\Domain\Model\Field;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineAgentRepository::class)]
#[ORM\Table]
class Agent implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\Column(unique: true)]
    public string $name;

    #[ORM\Column(unique: true)]
    public string $apiKey;

    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'agent')]
    public Collection $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id?->toString() ?? '';
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->name;
    }
}
