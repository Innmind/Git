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
    private $execute;

    public function __construct(Binary $binary)
    {
        $this->execute = $binary;
    }

    /**
     * @return SetInterface<Remote>
     */
    public function all(): SetInterface
    {
        $remotes = new Str((string) ($this->execute)('remote'));

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
            $this->execute,
            $name
        );
    }

    public function add(Name $name, Url $url): Remote
    {
        ($this->execute)("remote add $name $url");

        return $this->get($name);
    }

    public function remove(Name $name): self
    {
        ($this->execute)("remote remove $name");

        return $this;
    }
}
