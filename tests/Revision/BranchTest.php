<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\Revision\Branch;
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
            Branch::class,
            Branch::maybe('master')->match(
                static fn($branch) => $branch,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidBranchName()
    {
        $this
            ->forAll(Set\Strings::any()->filter(static fn($string) => !\preg_match('~^\w+$~', $string)))
            ->then(function($string): void {
                $this->assertNull(Branch::maybe($string)->match(
                    static fn($branch) => $branch,
                    static fn() => null,
                ));
            });
    }

    public function testNamesAreAccepted()
    {
        $names = static fn($min = 0) => Set\Decorate::immutable(
            static fn($chars) => \implode('', $chars),
            Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($ord) => \chr($ord),
                    Set\Either::any(
                        Set\Integers::between(65, 90), // A-Z
                        Set\Integers::between(97, 122), // a-z
                    ),
                ),
            )->between($min, 20),
        );

        $this
            ->forAll(
                $names(1),
                $names(),
            )
            ->then(function($first, $second): void {
                $this->assertSame($first, Branch::maybe($first)->match(
                    static fn($branch) => $branch->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'-'.$second, Branch::maybe($first.'-'.$second)->match(
                    static fn($branch) => $branch->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'/'.$second, Branch::maybe($first.'/'.$second)->match(
                    static fn($branch) => $branch->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'.'.$second, Branch::maybe($first.'.'.$second)->match(
                    static fn($branch) => $branch->toString(),
                    static fn() => null,
                ));
            });
    }
}
