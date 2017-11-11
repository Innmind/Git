<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Revision;

use Innmind\Git\{
    Revision\Branch,
    Revision,
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

    public function testInterface()
    {
        $this->assertInstanceOf(
            Revision::class,
            new Branch('master')
        );
    }

    public function testThrowWhenInvalidBranchName()
    {
        $this
            ->forAll(Generator\string())
            ->then(function($string): void {
                $this->expectException(DomainException::class);

                new Branch($string);
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
                $this->assertSame($first, (string) new Branch($first));
                $this->assertSame($first.'-'.$second, (string) new Branch($first.'-'.$second));
                $this->assertSame($first.'/'.$second, (string) new Branch($first.'/'.$second));
            });
    }
}
