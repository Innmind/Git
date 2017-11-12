<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Tag;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private $value;

    public function __construct(string $name)
    {
        if ((new Str($name))->trim()->length() === 0) {
            throw new DomainException;
        }

        $this->value = $name;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
