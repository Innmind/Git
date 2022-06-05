<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag,
    Repository\Tag\Name,
    Message
};
use Innmind\TimeContinuum\PointInTime;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testInterface()
    {
        $tag = new Tag(
            $name = Name::of('1.0.0'),
            $message = Message::of('watev'),
            $date = $this->createMock(PointInTime::class),
        );

        $this->assertSame($name, $tag->name());
        $this->assertSame($message, $tag->message());
        $this->assertSame($date, $tag->date());
    }
}
