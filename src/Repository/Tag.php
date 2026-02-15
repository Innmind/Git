<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag\Name,
    Message,
};
use Innmind\Time\Point;

final class Tag
{
    private function __construct(
        private Name $name,
        private Message $message,
        private Point $date,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Name $name,
        Message $message,
        Point $date,
    ): self {
        return new self($name, $message, $date);
    }

    #[\NoDiscard]
    public function name(): Name
    {
        return $this->name;
    }

    #[\NoDiscard]
    public function message(): Message
    {
        return $this->message;
    }

    #[\NoDiscard]
    public function date(): Point
    {
        return $this->date;
    }
}
