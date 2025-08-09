<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision\Branch,
    Revision\Hash,
};
use Innmind\Immutable\{
    Set,
    Str,
    Attempt,
    SideEffect,
    Monoid\Concat,
};

final class Branches
{
    public function __construct(private Binary $binary)
    {
    }

    /**
     * @return Set<Branch>
     */
    #[\NoDiscard]
    public function local(): Set
    {
        $branches = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color'),
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat);

        /** @var Set<Branch> */
        return Set::of(
            ...$branches
                ->split("\n")
                ->filter(static function(Str $line): bool {
                    return !$line->matches('~HEAD detached~');
                })
                ->filter(static fn(Str $line): bool => !$line->trim()->empty())
                ->map(
                    static fn(Str $branch) => Branch::maybe(
                        $branch->drop(2)->toString(),
                    )->match(
                        static fn($branch) => $branch,
                        static fn() => null,
                    ),
                )
                ->filter(static fn($branch) => $branch instanceof Branch)
                ->toList(),
        );
    }

    /**
     * @return Set<Branch>
     */
    #[\NoDiscard]
    public function remote(): Set
    {
        $branches = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('r')
                ->withOption('no-color'),
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat);

        /** @var Set<Branch> */
        return Set::of(
            ...$branches
                ->split("\n")
                ->filter(static function(Str $line): bool {
                    return !$line->matches('~-> origin/~');
                })
                ->filter(static fn(Str $line): bool => !$line->trim()->empty())
                ->map(
                    static fn(Str $branch) => Branch::maybe(
                        $branch->drop(2)->toString(),
                    )->match(
                        static fn($branch) => $branch,
                        static fn() => null,
                    ),
                )
                ->filter(static fn($branch) => $branch instanceof Branch)
                ->toList(),
        );
    }

    /**
     * @return Set<Branch>
     */
    #[\NoDiscard]
    public function all(): Set
    {
        return $this
            ->local()
            ->merge($this->remote());
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function new(Branch $name, Hash|Branch|null $off = null): Attempt
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('branch')
            ->withArgument($name->toString());

        if ($off) {
            $command = $command->withArgument($off->toString());
        }

        return ($this->binary)($command)->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function newOrphan(Branch $name): Attempt
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('checkout')
            ->withOption('orphan')
            ->withArgument($name->toString());

        return ($this->binary)($command)->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function delete(Branch $name): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('d')
                ->withArgument($name->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function forceDelete(Branch $name): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('D')
                ->withArgument($name->toString()),
        )->map(static fn() => new SideEffect);
    }
}
