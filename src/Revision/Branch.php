<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Branch
{
    private string $value;

    private function __construct(string $branch)
    {
        $this->value = $branch;
    }

    /**
     * @param literal-string $branch
     *
     * @throws DomainException
     */
    public static function of(string $branch): self
    {
        return self::maybe($branch)->match(
            static fn($self) => $self,
            static fn() => throw new DomainException($branch),
        );
    }

    /**
     * @return Maybe<self>
     */
    public static function maybe(string $branch): Maybe
    {
        if (!Str::of($branch)->matches('~^[\w\-\/\.]+$~')) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($branch));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
