<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\CommandFailed;
use Innmind\Server\Control\{
    Server,
    Server\Command,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\Str;

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
        $process->wait();

        if (!$process->exitCode()->successful()) {
            throw new CommandFailed($command, $process);
        }

        $parts = Str::of($process->output()->toString())->capture(
            '~version (?<major>\d+)\.(?<minor>\d+)\.(?<bugfix>\d+)~',
        );

        return new Version(
            (int) $parts->get('major')->toString(),
            (int) $parts->get('minor')->toString(),
            (int) $parts->get('bugfix')->toString(),
        );
    }
}
