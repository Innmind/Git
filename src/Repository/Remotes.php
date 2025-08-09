<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url,
};
use Innmind\Immutable\{
    Set,
    Maybe,
    SideEffect,
    Monoid\Concat,
};

final class Remotes
{
    private Binary $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return Set<Remote>
     */
    #[\NoDiscard]
    public function all(): Set
    {
        $remotes = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
        )
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat);

        /** @var Set<Remote> */
        return Set::of(
            ...$remotes
                ->split("\n")
                ->map(
                    fn($remote) => Name::maybe($remote->toString())
                        ->map($this->get(...))
                        ->match(
                            static fn($remote) => $remote,
                            static fn() => null,
                        ),
                )
                ->filter(static fn($remote) => $remote instanceof Remote)
                ->toList(),
        );
    }

    #[\NoDiscard]
    public function get(Name $name): Remote
    {
        return new Remote(
            $this->binary,
            $name,
        );
    }

    #[\NoDiscard]
    public function add(Name $name, Url $url): Remote
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('add')
                ->withArgument($name->toString())
                ->withArgument($url->toString()),
        );

        return $this->get($name);
    }

    /**
     * @return Maybe<SideEffect>
     */
    #[\NoDiscard]
    public function remove(Name $name): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('remove')
                ->withArgument($name->toString()),
        )->map(static fn() => new SideEffect);
    }
}
