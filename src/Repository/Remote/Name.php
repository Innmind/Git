<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Remote;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $value;

    public function __construct(string $remote)
    {
        $remote = new Str($remote);

        if (!$remote->matches('~\w+~')) {
            throw new DomainException;
        }

        $this->value = (string) $remote;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
