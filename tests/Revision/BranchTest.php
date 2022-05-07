<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\{
    Revision\Branch,
    Revision,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class BranchTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Revision::class,
            new Branch('master'),
        );
    }

    public function testThrowWhenInvalidBranchName()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Branch($string);
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
                $this->assertSame($first, (new Branch($first))->toString());
                $this->assertSame($first.'-'.$second, (new Branch($first.'-'.$second))->toString());
                $this->assertSame($first.'/'.$second, (new Branch($first.'/'.$second))->toString());
            });
    }
}
