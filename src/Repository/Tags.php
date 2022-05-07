<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision,
    Message,
    Repository\Tag\Name,
    Exception\DomainException,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\Format\RFC2822,
};
use Innmind\Immutable\{
    Set,
    Str,
};

final class Tags
{
    private Binary $binary;
    private Clock $clock;

    public function __construct(Binary $binary, Clock $clock)
    {
        $this->binary = $binary;
        $this->clock = $clock;
    }

    public function push(): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withOption('tags'),
        );
    }

    public function add(Name $name, Message $message = null): void
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('tag')
            ->withArgument($name->toString());

        if (null !== $message) {
            $command = $command
                ->withShortOption('a')
                ->withShortOption('m')
                ->withArgument($message->toString());
        }

        ($this->binary)($command);
    }

    public function sign(Name $name, Message $message): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withShortOption('s')
                ->withShortOption('a')
                ->withArgument($name->toString())
                ->withShortOption('m')
                ->withArgument($message->toString()),
        );
    }

    /**
     * @return Set<Tag>
     */
    public function all(): Set
    {
        $output = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withOption('list')
                ->withOption('format', '%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)')
        );
        $output = Str::of($output->toString());

        /** @var Set<Tag> */
        return Set::of(
            ...$output
                ->split("\n")
                ->filter(static function(Str $line): bool {
                    return !$line->trim()->empty();
                })
                ->map(function(Str $line): ?Tag {
                    [$name, $message, $time] = $line->split('|||')->toList();

                    return $this
                        ->clock
                        ->at($time->toString(), new RFC2822)
                        ->match(
                            static fn($date) => new Tag(
                                new Name($name->toString()),
                                new Message($message->toString()),
                                $date,
                            ),
                            static fn() => null,
                        );
                })
                ->filter(static fn($tag) => $tag !== null)
                ->toList(),
        );
    }
}
