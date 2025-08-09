<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag\Name,
    Message,
};
use Innmind\TimeContinuum\PointInTime;

final class Tag
{
    private function __construct(
        private Name $name,
        private Message $message,
        private PointInTime $date,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Name $name,
        Message $message,
        PointInTime $date,
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
    public function date(): PointInTime
    {
        return $this->date;
    }
}
