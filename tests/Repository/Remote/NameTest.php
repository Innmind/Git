<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\{
    Repository\Remote\Name,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class BranchTest extends TestCase
{
    use TestTrait;

    public function testThrowWhenInvalidRemoteName()
    {
        $this
            ->forAll(Generator\string())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Name($string);
            });
    }

    public function testNamesAreAccepted()
    {
        $this
            ->forAll(
                Generator\names(),
                Generator\names()
            )
            ->when(static function($first): bool {
                return strlen($first) > 1;
            })
            ->then(function($first, $second): void {
                $this->assertSame($first, (string) new Name($first));
                $this->assertSame($first.'-'.$second, (string) new Name($first.'-'.$second));
                $this->assertSame($first.'/'.$second, (string) new Name($first.'/'.$second));
            });
    }
}
