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
    public function __construct(
        private Name $name,
        private Message $message,
        private PointInTime $date,
    ) {
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
