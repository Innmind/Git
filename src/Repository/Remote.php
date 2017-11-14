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
    private $binary;
    private $name;

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
                ->withArgument((string) $this->name)
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
                ->withArgument((string) $this->name)
                ->withArgument((string) $url)
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
                ->withArgument((string) $this->name)
                ->withArgument((string) $url)
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
                ->withArgument((string) $this->name)
                ->withArgument((string) $url)
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
                ->withArgument((string) $this->name)
                ->withArgument((string) $branch)
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
                ->withArgument((string) $this->name)
                ->withArgument(':'.$branch)
        );

        return $this;
    }
}
