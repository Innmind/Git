<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Tag;

use Innmind\Git\Repository\Tag\Name;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class NameTest extends TestCase
{
    use TestTrait;

    public function testAcceptAnyNonEmptyString()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $name): bool {
                return strlen($name) > 0;
            })
            ->then(function(string $name): void {
                $this->assertSame($name, (string) new Name($name));
            });
    }

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenEmptyString()
    {
        new Name(' ');
    }
}
