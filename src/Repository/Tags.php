<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Message,
    Repository\Tag\Name,
};
use Innmind\TimeContinuum\{
    Clock,
    Format,
};
use Innmind\Immutable\{
    Set,
    Str,
    Attempt,
    Maybe,
    SideEffect,
    Monoid\Concat,
};

final class Tags
{
    private function __construct(
        private Binary $binary,
        private Clock $clock,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Binary $binary, Clock $clock): self
    {
        return new self($binary, $clock);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function push(): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withOption('tags'),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function add(Name $name, ?Message $message = null): Attempt
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

        return ($this->binary)($command)->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function sign(Name $name, Message $message): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withShortOption('s')
                ->withShortOption('a')
                ->withArgument($name->toString())
                ->withShortOption('m')
                ->withArgument($message->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Set<Tag>
     */
    #[\NoDiscard]
    public function all(): Set
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('tag')
                ->withOption('list')
                ->withOption('format', '%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)')
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat)
            ->split("\n")
            ->filter(static fn($line) => !$line->trim()->empty())
            ->flatMap(function(Str $line) {
                /** @psalm-suppress PossiblyUndefinedArrayOffset */
                [$name, $message, $time] = $line->split('|||')->toList();
                $time = $time->pregReplace(
                    '~, (\d) ~',
                    ', 0${1} ',
                );

                /** @psalm-suppress ArgumentTypeCoercion */
                return Maybe::all(
                    Name::maybe($name->toString()),
                    Message::maybe($message->toString()),
                    $this->clock->at($time->toString(), Format::rfc2822()),
                )
                    ->map(Tag::of(...))
                    ->toSequence();
            })
            ->toSet();
    }
}
