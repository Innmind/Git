<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Git\{
    Revision,
    Exception\DomainException
};
use Innmind\Immutable\Str;

final class Branch implements Revision
{
    private string $value;

    public function __construct(string $branch)
    {
        $branch = new Str($branch);

        if (!$branch->matches('~\w+~')) {
            throw new DomainException;
        }

        $this->value = (string) $branch;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
