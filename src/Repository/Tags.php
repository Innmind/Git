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
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str
};

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

    /**
     * @return SetInterface<Tag>
     */
    public function all(): SetInterface
    {
        $output = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withOption('list')
                ->withOption('format', '%(refname:strip=2)|||%(subject)')
        );
        $output = new Str((string) $output);

        return $output
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->trim()->empty();
            })
            ->reduce(
                new Set(Tag::class),
                static function(SetInterface $tags, Str $line): SetInterface {
                    [$name, $message] = $line->split('|||');

                    return $tags->add(new Tag(
                        new Name((string) $name),
                        new Message((string) $message)
                    ));
                }
            );
    }
}
