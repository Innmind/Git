<?php
declare(strict_types = 1);

namespace Innmind\Git\Revision;

use Innmind\Git\Revision;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Hash implements Revision
{
    private string $value;

    private function __construct(string $hash)
    {
        $this->value = $hash;
    }

    /**
     * @return Maybe<self>
     */
    public static function maybe(string $hash): Maybe
    {
        $hash = Str::of($hash);

        if (!$hash->matches('~^[a-z0-9]{7,40}$~')) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($hash->toString()));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
