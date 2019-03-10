<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag,
    Repository\Tag\Name,
    Message
};
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testInterface()
    {
        $tag = new Tag(
            $name = new Name('1.0.0'),
            $message = new Message('watev')
        );

        $this->assertSame($name, $tag->name());
        $this->assertSame($message, $tag->message());
    }
}
