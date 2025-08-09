<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision\Hash,
    Revision\Branch,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Checkout
{
    private Binary $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function file(Path $path): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument('--')
                ->withArgument($path->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function revision(Hash|Branch $revision): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument($revision->toString()),
        )->map(static fn() => new SideEffect);
    }
}
