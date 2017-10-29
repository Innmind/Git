<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\Version;
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

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenMajorTooLow()
    {
        new Version(-1, 1, 1);
    }

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenMinorTooLow()
    {
        new Version(1, -1, 1);
    }

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenBugfixTooLow()
    {
        new Version(1, 1, -1);
    }
}
