<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Git\{
    Revision,
    Exception\DomainException
};
use Innmind\Immutable\Str;

final class Hash implements Revision
{
    private string $value;

    public function __construct(string $hash)
    {
        $hash = Str::of($hash);

        if (
            !$hash->matches('~[a-z0-9]~') ||
            (
                $hash->length() !== 7 &&
                $hash->length() !== 40
            )
        ) {
            throw new DomainException;
        }

        $this->value = $hash->toString();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
