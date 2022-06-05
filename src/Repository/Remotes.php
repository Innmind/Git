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
    Str,
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
    public function all(): Set
    {
        $remotes = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
        )
            ->match(
                static fn($output) => Str::of($output->toString()),
                static fn() => Str::of(''),
            );

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

    public function get(Name $name): Remote
    {
        return new Remote(
            $this->binary,
            $name,
        );
    }

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

    public function remove(Name $name): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('remove')
                ->withArgument($name->toString()),
        );
    }
}
