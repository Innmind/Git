<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Immutable\Maybe;

final class Version
{
    /**
     * @param int<0, max> $major
     * @param int<0, max> $minor
     * @param int<0, max> $bugfix
     */
    private function __construct(
        private int $major,
        private int $minor,
        private int $bugfix,
    ) {
    }

    /**
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function of(int $major, int $minor, int $bugfix): Maybe
    {
        if (
            $major < 0 ||
            $minor < 0 ||
            $bugfix < 0
        ) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($major, $minor, $bugfix));
    }

    /**
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function major(): int
    {
        return $this->major;
    }

    /**
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function minor(): int
    {
        return $this->minor;
    }

    /**
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function bugfix(): int
    {
        return $this->bugfix;
    }
}
