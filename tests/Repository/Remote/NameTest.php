<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\{
    Repository\Remote\Name,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testThrowWhenInvalidRemoteName()
    {
        $this
            ->forAll(Set\Unicode::strings())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Name($string);
            });
    }

    public function testNamesAreAccepted()
    {
        $names = static fn($min = 0) => Set\Decorate::immutable(
            static fn($chars) => \implode('', $chars),
            Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($ord) => \chr($ord),
                    new Set\Either(
                        Set\Integers::between(65, 90), // A-Z
                        Set\Integers::between(97, 122), // a-z
                    ),
                ),
                Set\Integers::between($min, 20),
            ),
        );

        $this
            ->forAll(
                $names(1),
                $names(),
            )
            ->then(function($first, $second): void {
                $this->assertSame($first, (new Name($first))->toString());
                $this->assertSame($first.'-'.$second, (new Name($first.'-'.$second))->toString());
                $this->assertSame($first.'/'.$second, (new Name($first.'/'.$second))->toString());
            });
    }
}
