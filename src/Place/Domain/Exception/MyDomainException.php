<?php

declare(strict_types=1);

namespace App\Place\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;

#[ErrorResource]
class MyDomainException extends \Exception implements ProblemExceptionInterface
{
    public function getType(): string
    {
        return 'teapot11111111';
    }

    public function getTitle(): ?string
    {

        return null;
    }

    public function getStatus(): ?int
    {

        return 490;
    }

    public function getDetail(): ?string
    {

        return $this->getMessage();
    }

    public function getInstance(): ?string
    {

        return null;
    }
}