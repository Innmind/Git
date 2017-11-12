<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url,
    Revision\Branch
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

    public function push(Branch $branch): self
    {
        ($this->execute)(sprintf(
            'push -u %s %s',
            $this->name,
            $branch
        ));

        return $this;
    }

    public function remove(Branch $branch): self
    {
        ($this->execute)(sprintf(
            'push %s :%s',
            $this->name,
            $branch
        ));

        return $this;
    }
}
