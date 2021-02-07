<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\{
    Revision\Hash,
    Revision,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class HashTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Revision::class,
            new Hash('0000000')
        );
    }

    public function testThrowWhenInvalidHash()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Hash($string);
            });
    }

    public function testOnlyHashAreAccepted()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $hash = sha1($string);
                $short = substr($hash, 0, 7);

                $this->assertSame($hash, (new Hash($hash))->toString());
                $this->assertSame($short, (new Hash($short))->toString());
            });
    }
}
