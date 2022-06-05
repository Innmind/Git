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
    private string $value;

    private function __construct(string $remote)
    {
        $this->value = $remote;
    }

    /**
     * @param literal-string $remote
     *
     * @throws DomainException
     */
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
    public static function maybe(string $remote): Maybe
    {
        if (!Str::of($remote)->matches('~^[\w\-\/\.]+$~')) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($remote));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
