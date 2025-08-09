<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Remote;

use Innmind\Git\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Name
{
    private function __construct(private string $value)
    {
    }

    /**
     * @param literal-string $remote
     *
     * @throws DomainException
     */
    #[\NoDiscard]
    public static function of(string $remote): self
    {
        return self::maybe($remote)->match(
            static fn($self) => $self,
            static fn() => throw new DomainException($remote),
        );
    }

    /**
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $remote): Maybe
    {
        if (!Str::of($remote)->matches('~^[\w\-\/\.]+$~')) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($remote));
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
