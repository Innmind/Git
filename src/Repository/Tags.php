<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Message,
    Repository\Tag\Name,
};
use Innmind\Server\Control\Server\Command;
use Innmind\Time\{
    Clock,
    Format,
};
use Innmind\Immutable\{
    Sequence,
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
            static fn($command) => $command
                ->withArgument('push')
                ->withOption('tags'),
        )->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function add(Name $name, ?Message $message = null): Attempt
    {
        $map = static fn(Command $command): Command => $command
            ->withArgument('tag')
            ->withArgument($name->toString());

        if (null !== $message) {
            $map = static fn(Command $command): Command => $map($command)
                ->withShortOption('a')
                ->withShortOption('m')
                ->withArgument($message->toString());
        }

        return ($this->binary)($map)->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function sign(Name $name, Message $message): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('tag')
                ->withShortOption('s')
                ->withShortOption('a')
                ->withArgument($name->toString())
                ->withShortOption('m')
                ->withArgument($message->toString()),
        )->map(SideEffect::identity(...));
    }

    /**
     * @return Sequence<Tag>
     */
    #[\NoDiscard]
    public function all(): Sequence
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('tag')
                ->withOption('list')
                ->withOption('format', '%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)')
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(Concat::monoid)
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
                    $this->clock->at($time->toString(), Format::rfc2822())->maybe(),
                )
                    ->map(Tag::of(...))
                    ->toSequence();
            });
    }
}
