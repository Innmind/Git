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
    Maybe,
    SideEffect,
};

final class Repository
{
    private Binary $binary;
    private Clock $clock;

    private function __construct(
        Server $server,
        Path $path,
        Clock $clock,
        Path $home = null,
    ) {
        $this->binary = new Binary($server, $path, $home);
        $this->clock = $clock;
    }

    /**
     * @return Maybe<self>
     */
    public static function of(
        Server $server,
        Path $path,
        Clock $clock,
        Path $home = null,
    ): Maybe {
        /** @var Maybe<self> */
        return $server
            ->processes()
            ->execute(
                Command::foreground('mkdir')
                    ->withShortOption('p')
                    ->withArgument($path->toString()),
            )
            ->wait()
            ->match(
                static fn() => Maybe::just(new self($server, $path, $clock, $home)),
                static fn() => Maybe::nothing(),
            );
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function init(): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('init'),
        )
            ->map(static fn($output) => Str::of($output->toString()))
            ->filter(
                static fn($output) => $output->contains('Initialized empty Git repository') || $output->contains('Reinitialized existing Git repository'),
            )
            ->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<Hash|Branch>
     */
    public function head(): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color'),
        )
            ->match(
                static fn($output) => Str::of($output->toString()),
                static fn() => Str::of(''),
            )
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return $line->matches('~^\* .+~');
            })
            ->first()
            ->flatMap(self::parseRevision(...));
    }

    public function branches(): Branches
    {
        return new Branches($this->binary);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function push(): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push'),
        )->map(static fn() => new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function pull(): Maybe
    {
        return ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('pull'),
        )->map(static fn() => new SideEffect);
    }

    public function remotes(): Remotes
    {
        return new Remotes($this->binary);
    }

    public function checkout(): Checkout
    {
        return new Checkout($this->binary);
    }

    public function tags(): Tags
    {
        return new Tags($this->binary, $this->clock);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function add(Path $file): Maybe
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
     * @return Maybe<SideEffect>
     */
    public function commit(Message $message): Maybe
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
     * @return Maybe<SideEffect>
     */
    public function merge(Branch $branch): Maybe
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
     * @return Maybe<Hash|Branch>
     */
    private static function parseRevision(Str $revision): Maybe
    {
        /** @var Maybe<Hash|Branch> */
        return $revision
            ->capture('~\(HEAD detached at (?P<hash>[a-z0-9]{7,40})\)~')
            ->get('hash')
            ->match(
                static fn($hash) => Hash::maybe($hash->toString()),
                static fn() => Branch::maybe($revision->drop(2)->toString()),
            );
    }
}
