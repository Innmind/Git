<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Git
{
    private Server $server;
    private Clock $clock;
    private ?Path $home;

    private function __construct(Server $server, Clock $clock, Path $home = null)
    {
        $this->server = $server;
        $this->clock = $clock;
        $this->home = $home;
    }

    /**
     * @param Path|null $home Required for some operations like signing commits
     */
    public static function of(Server $server, Clock $clock, Path $home = null): self
    {
        return new self($server, $clock, $home);
    }

    /**
     * @return Maybe<Repository>
     */
    public function repository(Path $path): Maybe
    {
        return Repository::of($this->server, $path, $this->clock, $this->home);
    }

    /**
     * @return Maybe<Version>
     */
    public function version(): Maybe
    {
        $process = $this
            ->server
            ->processes()
            ->execute(
                Command::foreground('git')
                    ->withOption('version'),
            );
        /** @var Maybe<Output> */
        $output = $process
            ->wait()
            ->match(
                static fn() => Maybe::just($process->output()),
                static fn() => Maybe::nothing(),
            );

        return $output
            ->map(static fn($output) => Str::of($output->toString()))
            ->map(static fn($output) => $output->capture(
                '~version (?<major>\d+)\.(?<minor>\d+)\.(?<bugfix>\d+)~',
            ))
            ->map(
                static fn($parts) => $parts
                    ->map(static fn($_, $value) => $value->toString())
                    ->map(static fn($_, $value) => (int) $value),
            )
            ->flatMap(
                static fn($parts) => Maybe::all($parts->get('major'), $parts->get('minor'), $parts->get('bugfix'))
                    ->flatMap(static fn(int $major, int $minor, int $bugfix) => Version::of(
                        $major,
                        $minor,
                        $bugfix,
                    )),
            );
    }
}
