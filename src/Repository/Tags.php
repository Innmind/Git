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
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    Format\RFC2822
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str
};

final class Tags
{
    private $binary;
    private $clock;

    public function __construct(Binary $binary, TimeContinuumInterface $clock)
    {
        $this->binary = $binary;
        $this->clock = $clock;
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

    public function add(Name $name, Message $message = null): self
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('tag')
            ->withArgument((string) $name);

        if (null !== $message) {
            $command = $command
                ->withShortOption('a')
                ->withShortOption('m')
                ->withArgument((string) $message);
        }

        ($this->binary)($command);

        return $this;
    }

    public function sign(Name $name, Message $message): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withShortOption('s')
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
                ->withOption('format', '%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)')
        );
        $output = new Str((string) $output);

        return $output
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->trim()->empty();
            })
            ->reduce(
                new Set(Tag::class),
                function(SetInterface $tags, Str $line): SetInterface {
                    [$name, $message, $time] = $line->split('|||');

                    return $tags->add(new Tag(
                        new Name((string) $name),
                        new Message((string) $message),
                        $this->clock->at(
                            (string) $time,
                            new RFC2822
                        )
                    ));
                }
            );
    }
}
