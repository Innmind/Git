<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Message,
    Repository\Tag\Name,
    Exception\DomainException,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\Format\RFC2822,
    PointInTime,
};
use Innmind\Immutable\{
    Set,
    Str,
    Maybe,
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
        $output = $output->match(
            static fn($output) => Str::of($output->toString()),
            static fn() => Str::of(''),
        );

        /** @var Set<Tag> */
        return Set::of(
            ...$output
                ->split("\n")
                ->filter(static function(Str $line): bool {
                    return !$line->trim()->empty();
                })
                ->map(function(Str $line): ?Tag {
                    [$name, $message, $time] = $line->split('|||')->toList();

                    return Maybe::all(
                        Name::maybe($name->toString()),
                        Message::maybe($message->toString()),
                        $this->clock->at($time->toString(), new RFC2822),
                    )
                        ->map(static fn(Name $name, Message $message, PointInTime $date) => new Tag($name, $message, $date))
                        ->match(
                            static fn($tag) => $tag,
                            static fn() => null,
                        );
                })
                ->filter(static fn($tag) => $tag !== null)
                ->toList(),
        );
    }
}
