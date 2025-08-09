<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Hash
{
    private function __construct(private string $value)
    {
    }

    /**
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $hash): Maybe
    {
        $hash = Str::of($hash);

        if (!$hash->matches('~^[a-z0-9]{7,40}$~')) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($hash->toString()));
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
