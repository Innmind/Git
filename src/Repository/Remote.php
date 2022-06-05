<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Repository\Remote\Name,
    Repository\Remote\Url,
    Revision\Branch,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
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

    /**
     * @return Maybe<SideEffect>
     */
    public function prune(): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('prune')
                ->withArgument($this->name->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function setUrl(Url $url): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function addUrl(Url $url): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('add')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function deleteUrl(Url $url): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('delete')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString())
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function push(Branch $branch): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withShortOption('u')
                ->withArgument($this->name->toString())
                ->withArgument($branch->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function delete(Branch $branch): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
                ->withArgument($this->name->toString())
                ->withArgument(':'.$branch->toString()),
        )->map(static fn() => new SideEffect);
    }
}
