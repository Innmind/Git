<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\Repository\Remote\Name;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testReturnNothingWhenInvalidRemoteName()
    {
        $this
            ->forAll(Set::strings()->unicode())
            ->then(function($string): void {
                $this->assertNull(Name::maybe($string)->match(
                    static fn($name) => $name,
                    static fn() => null,
                ));
            });
    }

    public function testNamesAreAccepted()
    {
        $names = static fn($min = 0) => Set::strings()
            ->madeOf(
                Set::either(
                    Set::integers()->between(65, 90), // A-Z
                    Set::integers()->between(97, 122), // a-z
                )->map(\chr(...)),
            )
            ->between($min, 20);

        $this
            ->forAll(
                $names(1),
                $names(),
            )
            ->then(function($first, $second): void {
                $this->assertSame($first, Name::maybe($first)->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'-'.$second, Name::maybe($first.'-'.$second)->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'/'.$second, Name::maybe($first.'/'.$second)->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ));
                $this->assertSame($first.'.'.$second, Name::maybe($first.'.'.$second)->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ));
            });
    }
}
