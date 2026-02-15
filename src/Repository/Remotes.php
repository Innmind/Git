<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url,
};
use Innmind\Immutable\{
    Sequence,
    Attempt,
    SideEffect,
    Monoid\Concat,
};

final class Remotes
{
    private function __construct(private Binary $binary)
    {
    }

    /**
     * @internal
     */
    public static function of(Binary $binary): self
    {
        return new self($binary);
    }

    /**
     * @return Sequence<Remote>
     */
    #[\NoDiscard]
    public function all(): Sequence
    {
        return ($this->binary)(
            static fn($command) => $command->withArgument('remote'),
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(Concat::monoid)
            ->split("\n")
            ->flatMap(
                fn($remote) => Name::maybe($remote->toString())
                    ->map($this->get(...))
                    ->toSequence(),
            );
    }

    #[\NoDiscard]
    public function get(Name $name): Remote
    {
        return Remote::of(
            $this->binary,
            $name,
        );
    }

    /**
     * @return Attempt<Remote>
     */
    #[\NoDiscard]
    public function add(Name $name, Url $url): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('remote')
                ->withArgument('add')
                ->withArgument($name->toString())
                ->withArgument($url->toString()),
        )->map(fn() => $this->get($name));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function remove(Name $name): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('remote')
                ->withArgument('remove')
                ->withArgument($name->toString()),
        )->map(SideEffect::identity(...));
    }
}
