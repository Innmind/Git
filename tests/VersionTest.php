<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Version,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testInterface()
    {
        $version = new Version(1, 2, 3);

        $this->assertSame(1, $version->major());
        $this->assertSame(2, $version->minor());
        $this->assertSame(3, $version->bugfix());
    }

    public function testThrowWhenMajorTooLow()
    {
        $this->expectException(DomainException::class);

        new Version(-1, 1, 1);
    }

    public function testThrowWhenMinorTooLow()
    {
        $this->expectException(DomainException::class);

        new Version(1, -1, 1);
    }

    public function testThrowWhenBugfixTooLow()
    {
        $this->expectException(DomainException::class);

        new Version(1, 1, -1);
    }
}
