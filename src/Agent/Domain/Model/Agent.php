<?php

namespace App\Agent\Domain\Model;

use App\Field\Domain\Model\Field;
use Deprecated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table]
class Agent implements UserInterface, Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['communities', 'places'])]
    public ?Uuid $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['communities', 'places'])]
    public string $name;

    #[ORM\Column(unique: true)]
    public string $apiKey;

    /** @var Collection<int, Field> */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'agent')]
    public Collection $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id->toString();
    }

    public function getRoles(): array
    {
        return ['ROLE_AGENT'];
    }

    #[Deprecated('Implementing "'.self::class.'::eraseCredentials()" is deprecated since Symfony 7.3; add the #[\Deprecated] attribute on the method to signal its either empty or that you moved the logic elsewhere, typically to the "__serialize()" method.')]
    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->name;
    }
}
