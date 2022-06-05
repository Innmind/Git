<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\{
    Revision\Hash,
    Revision,
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
            Hash::maybe('0000000')->match(
                static fn($hash) => $hash,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidHash()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $this->assertNull(Hash::maybe($string)->match(
                    static fn($hash) => $hash,
                    static fn() => null,
                ));
            });
    }

    public function testOnlyHashAreAccepted()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $hash = \sha1($string);
                $short = \substr($hash, 0, 7);

                $this->assertSame($hash, Hash::maybe($hash)->match(
                    static fn($hash) => $hash->toString(),
                    static fn() => null,
                ));
                $this->assertSame($short, Hash::maybe($short)->match(
                    static fn($hash) => $hash->toString(),
                    static fn() => null,
                ));
            });
    }
}
