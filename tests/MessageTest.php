<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Message,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class MessageTest extends TestCase
{
    use BlackBox;

    public function testAcceptAnyNonEmptyString()
    {
        $this
            ->forAll(Set\Strings::atLeast(1)->filter(
                static fn($string) => $string === \trim($string),
            ))
            ->then(function(string $message): void {
                $this->assertSame($message, (new Message($message))->toString());
            });
    }

    public function testThrowWhenEmptyString()
    {
        $this->expectException(DomainException::class);

        new Message(' ');
    }
}
