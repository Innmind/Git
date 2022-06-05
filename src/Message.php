<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Message
{
    /** @var non-empty-string */
    private string $value;

    /**
     * @param non-empty-string $message
     */
    private function __construct(string $message)
    {
        $this->value = $message;
    }

    /**
     * @param non-empty-string $message
     */
    public static function of(string $message): self
    {
        return new self($message);
    }

    /**
     * @return Maybe<self>
     */
    public static function maybe(string $message): Maybe
    {
        if (Str::of($message)->trim()->empty()) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return Maybe::just(new self($message));
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
