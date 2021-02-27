<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision,
    Revision\Branch,
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
        $branches = Str::of(($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color'),
        )->toString());

        /** @var Set<Branch> */
        return $branches
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->matches('~HEAD detached~');
            })
            ->filter(static fn(Str $line): bool => !$line->trim()->empty())
            ->toSetOf(
                Branch::class,
                static fn(Str $branch): \Generator => yield new Branch(
                    $branch->substring(2)->toString(),
                ),
            );
    }

    /**
     * @return Set<Branch>
     */
    public function remote(): Set
    {
        $branches = Str::of(($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withShortOption('r')
                ->withOption('no-color'),
        )->toString());

        /** @var Set<Branch> */
        return $branches
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->matches('~-> origin/~');
            })
            ->filter(static fn(Str $line): bool => !$line->trim()->empty())
            ->toSetOf(
                Branch::class,
                static fn(Str $branch): \Generator => yield new Branch(
                    $branch->substring(2)->toString(),
                ),
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

    public function new(Branch $name, Revision $off = null): void
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
