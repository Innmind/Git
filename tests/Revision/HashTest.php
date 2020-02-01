<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\{
    Revision\Hash,
    Revision,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class HashTest extends TestCase
{
    use TestTrait;

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
            ->forAll(Generator\string())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Hash($string);
            });
    }

    public function testOnlyHashAreAccepted()
    {
        $this
            ->forAll(Generator\string())
            ->then(function($string): void {
                $hash = sha1($string);
                $short = substr($hash, 0, 7);

                $this->assertSame($hash, (new Hash($hash))->toString());
                $this->assertSame($short, (new Hash($short))->toString());
            });
    }
}
