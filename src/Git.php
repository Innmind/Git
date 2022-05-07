<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\{
    CommandFailed,
    RuntimeException,
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
};

final class Git
{
    private Server $server;
    private Clock $clock;

    public function __construct(Server $server, Clock $clock)
    {
        $this->server = $server;
        $this->clock = $clock;
    }

    public function repository(Path $path): Repository
    {
        return new Repository($this->server, $path, $this->clock);
    }

    public function version(): Version
    {
        $process = $this
            ->server
            ->processes()
            ->execute(
                $command = Command::foreground('git')
                    ->withOption('version'),
            );
        $_ = $process
            ->wait()
            ->match(
                static fn() => null,
                static fn() => throw new CommandFailed($command, $process),
            );

        $parts = Str::of($process->output()->toString())
            ->capture(
                '~version (?<major>\d+)\.(?<minor>\d+)\.(?<bugfix>\d+)~',
            )
            ->map(static fn($_, $value) => $value->toString())
            ->map(static fn($_, $value) => (int) $value);

        return Maybe::all($parts->get('major'), $parts->get('minor'), $parts->get('bugfix'))
            ->map(static fn(int $major, int $minor, int $bugfix) => new Version(
                $major,
                $minor,
                $bugfix,
            ))
            ->match(
                static fn($version) => $version,
                static fn() => throw new RuntimeException('Invalid version'),
            );
    }
}
