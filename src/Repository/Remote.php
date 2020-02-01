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
    private Binary $binary;
    private Name $name;

    public function __construct(Binary $binary, Name $name)
    {
        $this->binary = $binary;
        $this->name = $name;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function prune(): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('prune')
                ->withArgument($this->name->toString())
        );

        return $this;
    }

    public function setUrl(Url $url): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString())
        );

        return $this;
    }

    public function addUrl(Url $url): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('add')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString())
        );

        return $this;
    }

    public function deleteUrl(Url $url): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('delete')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString())
        );

        return $this;
    }

    public function push(Branch $branch): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withShortOption('u')
                ->withArgument($this->name->toString())
                ->withArgument($branch->toString())
        );

        return $this;
    }

    public function delete(Branch $branch): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withArgument($this->name->toString())
                ->withArgument(':'.$branch->toString())
        );

        return $this;
    }
}
