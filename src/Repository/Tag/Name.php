<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Tag;

use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Name
{
    /** @var non-empty-string */
    private string $value;

    /**
     * @param non-empty-string $name
     */
    private function __construct(string $name)
    {
        $this->value = $name;
    }

    /**
     * @param non-empty-string $name
     */
    public static function of(string $name): self
    {
        return new self($name);
    }

    /**
     * @return Maybe<self>
     */
    public static function maybe(string $name): Maybe
    {
        if (Str::of($name)->trim()->empty()) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return Maybe::just(new self($name));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
