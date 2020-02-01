<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Tag;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $value;

    public function __construct(string $name)
    {
        if (Str::of($name)->trim()->empty()) {
            throw new DomainException;
        }

        $this->value = $name;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
