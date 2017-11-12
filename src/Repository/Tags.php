<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision,
    Message,
    Repository\Tag\Name,
    Exception\DomainException
};
use Innmind\Url\PathInterface;

final class Tags
{
    private $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    public function push(): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withOption('tags')
        );

        return $this;
    }

    public function add(Name $name, Message $message): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withShortOption('a')
                ->withArgument((string) $name)
                ->withShortOption('m')
                ->withArgument((string) $message)
        );

        return $this;
    }
}
