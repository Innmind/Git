<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Tag;

use Innmind\Git\Repository\Tag\Name;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testAcceptAnyNonEmptyString(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->atLeast(1)->filter(static fn($name) => $name === \trim($name)))
            ->prove(function(string $name): void {
                $this->assertSame($name, Name::maybe($name)->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ));
            });
    }

    public function testReturnNothingWhenEmptyString()
    {
        $this->assertNull(Name::maybe(' ')->match(
            static fn($name) => $name,
            static fn() => null,
        ));
    }
}
