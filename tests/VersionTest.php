<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testInterface()
    {
        $version = Version::of(1, 2, 3)->match(
            static fn($version) => $version,
            static fn() => null,
        );

        $this->assertSame(1, $version->major());
        $this->assertSame(2, $version->minor());
        $this->assertSame(3, $version->bugfix());
    }

    public function testReturnNothingWhenMajorTooLow()
    {
        $this->assertNull(Version::of(-1, 1, 1)->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenMinorTooLow()
    {
        $this->assertNull(Version::of(1, -1, 1)->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenBugfixTooLow()
    {
        $this->assertNull(Version::of(1, 1, -1)->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }
}
