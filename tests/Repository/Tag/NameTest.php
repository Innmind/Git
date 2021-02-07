<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Tag;

use Innmind\Git\{
    Repository\Tag\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testAcceptAnyNonEmptyString()
    {
        $this
            ->forAll(Set\Strings::atLeast(1)->filter(static fn($name) => $name === \trim($name)))
            ->then(function(string $name): void {
                $this->assertSame($name, (new Name($name))->toString());
            });
    }

    public function testThrowWhenEmptyString()
    {
        $this->expectException(DomainException::class);

        new Name(' ');
    }
}
