<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision,
    Exception\DomainException
};
use Innmind\Url\PathInterface;

final class Tags
{
    private $execute;

    public function __construct(Binary $binary)
    {
        $this->execute = $binary;
    }

    public function push(): self
    {
        ($this->execute)('push --tags');

        return $this;
    }

    public function add(string $name, string $message): self
    {
        if ($name === '' || $message === '') {
            throw new DomainException;
        }

        ($this->execute)("tag -a $name -m '$message'");

        return $this;
    }
}
