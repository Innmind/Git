<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Message,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class MessageTest extends TestCase
{
    use TestTrait;

    public function testAcceptAnyNonEmptyString()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $message): bool {
                return strlen($message) > 0;
            })
            ->then(function(string $message): void {
                $this->assertSame($message, (string) new Message($message));
            });
    }

    public function testThrowWhenEmptyString()
    {
        $this->expectException(DomainException::class);

        new Message(' ');
    }
}
