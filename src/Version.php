<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\DomainException;

final class Version
{
    private int $major;
    private int $minor;
    private int $bugfix;

    public function __construct(int $major, int $minor, int $bugfix)
    {
        if (\min($major, $minor, $bugfix) < 0) {
            throw new DomainException("$major.$minor.$bugfix");
        }

        $this->major = $major;
        $this->minor = $minor;
        $this->bugfix = $bugfix;
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
