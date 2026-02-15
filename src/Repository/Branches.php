<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision\Branch,
    Revision\Hash,
};
use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\{
    Sequence,
    Str,
    Attempt,
    SideEffect,
    Monoid\Concat,
};

final class Branches
{
    private function __construct(private Binary $binary)
    {
    }

    /**
     * @internal
     */
    public static function of(Binary $binary): self
    {
        return new self($binary);
    }

    /**
     * @return Sequence<Branch>
     */
    #[\NoDiscard]
    public function local(): Sequence
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('branch')
                ->withOption('no-color'),
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(Concat::monoid)
            ->split("\n")
            ->filter(static fn($line) => !$line->matches('~HEAD detached~'))
            ->filter(static fn($line) => !$line->trim()->empty())
            ->flatMap(
                static fn(Str $branch) => Branch::maybe(
                    $branch->drop(2)->toString(),
                )->toSequence(),
            );
    }

    /**
     * @return Sequence<Branch>
     */
    #[\NoDiscard]
    public function remote(): Sequence
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('branch')
                ->withShortOption('r')
                ->withOption('no-color'),
        )
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($output) => $output)
            ->map(static fn($chunk) => $chunk->data())
            ->fold(Concat::monoid)
            ->split("\n")
            ->filter(static fn($line) => !$line->matches('~-> origin/~'))
            ->filter(static fn($line) => !$line->trim()->empty())
            ->flatMap(
                static fn(Str $branch) => Branch::maybe(
                    $branch->drop(2)->toString(),
                )->toSequence(),
            );
    }

    /**
     * @return Sequence<Branch>
     */
    #[\NoDiscard]
    public function all(): Sequence
    {
        return $this
            ->local()
            ->append($this->remote());
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function new(Branch $name, Hash|Branch|null $off = null): Attempt
    {
        $map = static fn(Command $command): Command => $command
            ->withArgument('branch')
            ->withArgument($name->toString());

        if ($off) {
            $map = static fn(Command $command): Command => $map($command)
                ->withArgument($off->toString());
        }

        return ($this->binary)($map)->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function newOrphan(Branch $name): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('checkout')
                ->withOption('orphan')
                ->withArgument($name->toString()),
        )->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function delete(Branch $name): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('branch')
                ->withShortOption('d')
                ->withArgument($name->toString()),
        )->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function forceDelete(Branch $name): Attempt
    {
        return ($this->binary)(
            static fn($command) => $command
                ->withArgument('branch')
                ->withShortOption('D')
                ->withArgument($name->toString()),
        )->map(SideEffect::identity(...));
    }
}
