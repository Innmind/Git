<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tag\Name,
    Message
};

final class Tag
{
    private $name;
    private $message;

    public function __construct(Name $name, Message $message)
    {
        $this->name = $name;
        $this->message = $message;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function message(): Message
    {
        return $this->message;
    }
}
