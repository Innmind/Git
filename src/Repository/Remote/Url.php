<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Remote;

use Innmind\Git\Exception\DomainException;
use Innmind\Url\Url as BaseUrl;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * Can be any valid url or a string of the format "user@server:repository.git"
 */
final class Url
{
    private string $value;

    private function __construct(string $url)
    {
        $this->value = $url;
    }

    /**
     * @param literal-string $url
     *
     * @throws DomainException
     */
    public static function of(string $url): self
    {
        return self::maybe($url)->match(
            static fn($self) => $self,
            static fn() => throw new DomainException($url),
        );
    }

    /**
     * @return Maybe<self>
     */
    public static function maybe(string $url): Maybe
    {
        return BaseUrl::maybe($url)
            ->otherwise(
                static fn() => Maybe::just(Str::of($url))
                    ->filter(static fn($url) => $url->matches('~^\S+@\S+(\.\S+)?:\S+(/\S+)?\.git$~')),
            )
            ->map(static fn($url) => new self($url->toString()));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
