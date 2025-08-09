<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Tag;

use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Name
{
    /**
     * @param non-empty-string $value
     */
    private function __construct(private string $value)
    {
    }

    /**
     * @param non-empty-string $name
     */
    #[\NoDiscard]
    public static function of(string $name): self
    {
        return new self($name);
    }

    /**
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $name): Maybe
    {
        if (Str::of($name)->trim()->empty()) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return Maybe::just(new self($name));
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
