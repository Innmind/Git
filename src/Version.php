<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Immutable\Maybe;

final class Version
{
    private int $major;
    private int $minor;
    private int $bugfix;

    private function __construct(int $major, int $minor, int $bugfix)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->bugfix = $bugfix;
    }

    /**
     * @return Maybe<self>
     */
    public static function of(int $major, int $minor, int $bugfix): Maybe
    {
        if (\min($major, $minor, $bugfix) < 0) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($major, $minor, $bugfix));
    }

    public function major(): int
    {
        return $this->major;
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function bugfix(): int
    {
        return $this->bugfix;
    }
}
