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
};

final class Branches
{
    private Binary $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return Set<Branch>
     */
    public function local(): Set
    {
        $branches = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color'),
        )
            ->match(
                static fn($output) => Str::of($output->toString()),
                static fn() => Str::of(''),
            );

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
            ->match(
                static fn($output) => Str::of($output->toString()),
                static fn() => Str::of(''),
            );

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
    public function all(): Set
    {
        return $this
            ->local()
            ->merge($this->remote());
    }

    public function new(Branch $name, Hash|Branch $off = null): void
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('branch')
            ->withArgument($name->toString());

        if ($off) {
            $command = $command->withArgument($off->toString());
        }

        ($this->binary)($command);
    }

    public function newOrphan(Branch $name): void
    {
        $command = $this
            ->binary
            ->command()
            ->withArgument('checkout')
            ->withOption('orphan')
            ->withArgument($name->toString());

        ($this->binary)($command);
    }

    public function delete(Branch $name): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('d')
                ->withArgument($name->toString()),
        );
    }

    public function forceDelete(Branch $name): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('D')
                ->withArgument($name->toString()),
        );
    }
}
