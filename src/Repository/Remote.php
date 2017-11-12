<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url
};

final class Remote
{
    private $execute;
    private $name;

    public function __construct(Binary $binary, Name $name)
    {
        $this->execute = $binary;
        $this->name = $name;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function prune(): self
    {
        ($this->execute)('remote prune '.$this->name);

        return $this;
    }

    public function setUrl(Url $url): self
    {
        ($this->execute)(sprintf(
            'remote set-url %s %s',
            $this->name,
            $url
        ));

        return $this;
    }

    public function addUrl(Url $url): self
    {
        ($this->execute)(sprintf(
            'remote set-url --add %s %s',
            $this->name,
            $url
        ));

        return $this;
    }

    public function removeUrl(Url $url): self
    {
        ($this->execute)(sprintf(
            'remote set-url --delete %s %s',
            $this->name,
            $url
        ));

        return $this;
    }
}
