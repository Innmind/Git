<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag\Name,
    Message
};
use Innmind\TimeContinuum\PointInTime;

final class Tag
{
    private Name $name;
    private Message $message;
    private PointInTime $date;

    public function __construct(Name $name, Message $message, PointInTime $date)
    {
        $this->name = $name;
        $this->message = $message;
        $this->date = $date;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function date(): PointInTime
    {
        return $this->date;
    }
}
