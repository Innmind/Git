<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Server\Control\{
    Server,
    Server\Command,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Monoid\Concat,
};

final class Git
{
    private Server $server;
    private Clock $clock;
    private ?Path $home;

    private function __construct(Server $server, Clock $clock, ?Path $home = null)
    {
        $this->server = $server;
        $this->clock = $clock;
        $this->home = $home;
    }

    /**
     * @param Path|null $home Required for some operations like signing commits
     */
    #[\NoDiscard]
    public static function of(Server $server, Clock $clock, ?Path $home = null): self
    {
        return new self($server, $clock, $home);
    }

    /**
     * @return Attempt<Repository>
     */
    #[\NoDiscard]
    public function repository(Path $path): Attempt
    {
        return Repository::of($this->server, $path, $this->clock, $this->home);
    }

    /**
     * @return Attempt<Version>
     */
    #[\NoDiscard]
    public function version(): Attempt
    {
        return $this
            ->server
            ->processes()
            ->execute(
                Command::foreground('git')
                    ->withOption('version'),
            )
            ->flatMap(
                static fn($process) => $process
                    ->wait()
                    ->attempt(static fn($error) => new \RuntimeException($error::class)),
            )
            ->map(
                static fn($success) => $success
                    ->output()
                    ->map(static fn($chunk) => $chunk->data())
                    ->fold(new Concat)
                    ->capture(
                        '~version (?<major>\d+)\.(?<minor>\d+)\.(?<bugfix>\d+)~',
                    )
                    ->map(static fn($_, $value) => $value->toString())
                    ->map(static fn($_, $value) => (int) $value),
            )
            ->flatMap(
                static fn($parts) => Maybe::all($parts->get('major'), $parts->get('minor'), $parts->get('bugfix'))
                    ->flatMap(Version::of(...))
                    ->attempt(static fn() => new \RuntimeException('Invalid version')),
            );
    }
}
