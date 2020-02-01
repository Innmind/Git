<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url
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
        $remotes = Str::of(($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
        )->toString());

        return $remotes
            ->split("\n")
            ->reduce(
                Set::of(Remote::class),
                function(Set $remotes, Str $remote): Set {
                    return $remotes->add(
                        $this->get(new Name($remote->toString()))
                    );
                }
            );
    }

    public function get(Name $name): Remote
    {
        return new Remote(
            $this->binary,
            $name
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
                ->withArgument($url->toString())
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
                ->withArgument($name->toString())
        );
    }
}
