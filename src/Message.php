<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\Str;

final class Message
{
    private string $value;

    public function __construct(string $message)
    {
        if ((new Str($message))->trim()->length() === 0) {
            throw new DomainException;
        }

        $this->value = $message;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
