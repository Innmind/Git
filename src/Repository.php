<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\{
    Revision\Hash,
    Revision\Branch,
    Repository\Branches,
    Repository\Remotes,
    Repository\Checkout,
    Repository\Tags,
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Str,
    Attempt,
    SideEffect,
    Monoid\Concat,
};

final class Repository
{
    private Binary $binary;
    private Clock $clock;

    private function __construct(
        Server $server,
        Path $path,
        Clock $clock,
        ?Path $home = null,
    ) {
        $this->binary = new Binary($server, $path, $home);
        $this->clock = $clock;
    }

    /**
     * @return Attempt<self>
     */
    #[\NoDiscard]
    public static function of(
        Server $server,
        Path $path,
        Clock $clock,
        ?Path $home = null,
    ): Attempt {
        return $server
            ->processes()
            ->execute(
                Command::foreground('mkdir')
                    ->withShortOption('p')
                    ->withArgument($path->toString()),
            )
            ->flatMap(
                static fn($process) => $process
                    ->wait()
                    ->attempt(static fn($error) => new \RuntimeException($error::class)),
            )
            ->map(static fn() => new self($server, $path, $clock, $home));
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function init(): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('init'),
        )
            ->map(
                static fn($output) => $output
                    ->map(static fn($chunk) => $chunk->data())
                    ->fold(new Concat),
            )
            ->flatMap(
                static fn($output) => match ($output->contains('Initialized empty Git repository') || $output->contains('Reinitialized existing Git repository')) {
                    true => Attempt::result($output),
                    false => Attempt::error(new \RuntimeException($output->toString())),
                },
            )
            ->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<Hash|Branch>
     */
    #[\NoDiscard]
    public function head(): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color'),
        )
            ->flatMap(
                static fn($output) => $output
                    ->map(static fn($chunk) => $chunk->data())
                    ->fold(new Concat)
                    ->split("\n")
                    ->filter(static function(Str $line): bool {
                        return $line->matches('~^\* .+~');
                    })
                    ->first()
                    ->attempt(static fn() => new \RuntimeException('Revision not found')),
            )
            ->flatMap(self::parseRevision(...));
    }

    #[\NoDiscard]
    public function branches(): Branches
    {
        return new Branches($this->binary);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function push(): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push'),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function pull(): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('pull'),
        )->map(static fn() => new SideEffect);
    }

    #[\NoDiscard]
    public function remotes(): Remotes
    {
        return new Remotes($this->binary);
    }

    #[\NoDiscard]
    public function checkout(): Checkout
    {
        return new Checkout($this->binary);
    }

    #[\NoDiscard]
    public function tags(): Tags
    {
        return new Tags($this->binary, $this->clock);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function add(Path $file): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('add')
                ->withArgument($file->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function commit(Message $message): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('commit')
                ->withShortOption('m')
                ->withArgument($message->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function merge(Branch $branch): Attempt
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('merge')
                ->withArgument($branch->toString()),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Attempt<Hash|Branch>
     */
    private static function parseRevision(Str $revision): Attempt
    {
        /** @var Attempt<Hash|Branch> */
        return $revision
            ->capture('~\(HEAD detached at (?P<hash>[a-z0-9]{7,40})\)~')
            ->get('hash')
            ->match(
                static fn($hash) => Hash::maybe($hash->toString()),
                static fn() => Branch::maybe($revision->drop(2)->toString()),
            )
            ->attempt(static fn() => new \RuntimeException("Invalid revision '{$revision->toString()}'"));
    }
}
