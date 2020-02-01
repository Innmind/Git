<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Git\{
    Revision,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class Branch implements Revision
{
    private string $value;

    public function __construct(string $branch)
    {
        if (!Str::of($branch)->matches('~\w+~')) {
            throw new DomainException($branch);
        }

        $this->value = $branch;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
