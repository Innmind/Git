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
    Attempt,
    SideEffect,
};

final class Remote
{
    private function __construct(
        private Binary $binary,
        private Name $name,
    ) {
        $this->binary = $binary;
        $this->name = $name;
    }

    /**
     * @internal
     */
    public static function of(Binary $binary, Name $name): self
    {
        return new self($binary, $name);
    }

    #[\NoDiscard]
    public function name(): Name
    {
        return $this->name;
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function prune(): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('remote')
                ->withArgument('prune')
                ->withArgument($this->name->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function setUrl(Url $url): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function addUrl(Url $url): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('add')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function deleteUrl(Url $url): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('remote')
                ->withArgument('set-url')
                ->withOption('delete')
                ->withArgument($this->name->toString())
                ->withArgument($url->toString())
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function push(Branch $branch): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('push')
                ->withShortOption('u')
                ->withArgument($this->name->toString())
                ->withArgument($branch->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function delete(Branch $branch): Attempt
    {
        return ($this->binary)(
            fn($command) => $command
                ->withArgument('push')
                ->withArgument($this->name->toString())
                ->withArgument(':'.$branch->toString()),
        )->map(static fn() => new SideEffect);
    }
}
