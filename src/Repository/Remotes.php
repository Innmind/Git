<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str
};

final class Remotes
{
    private Binary $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return SetInterface<Remote>
     */
    public function all(): SetInterface
    {
        $remotes = new Str((string) ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
        ));

        return $remotes
            ->split("\n")
            ->reduce(
                new Set(Remote::class),
                function(Set $remotes, Str $remote): Set {
                    return $remotes->add(
                        $this->get(new Name((string) $remote))
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
                ->withArgument((string) $name)
                ->withArgument((string) $url)
        );

        return $this->get($name);
    }

    public function remove(Name $name): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('remove')
                ->withArgument((string) $name)
        );

        return $this;
    }
}
