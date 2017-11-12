<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\CommandFailed;
use Innmind\Server\Control\{
    Server,
    Server\Command
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\Str;

final class Git
{
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function repository(PathInterface $path): Repository
    {
        return new Repository($this->server, $path);
    }

    public function version(): Version
    {
        $process = $this
            ->server
            ->processes()
            ->execute(
                $command = Command::foreground('git')
                    ->withOption('version')
            )
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            throw new CommandFailed($command, $process);
        }

        $parts = (new Str((string) $process->output()))->capture(
            '~version (?<major>\d+)\.(?<minor>\d+)\.(?<bugfix>\d+)~'
        );

        return new Version(
            (int) (string) $parts->get('major'),
            (int) (string) $parts->get('minor'),
            (int) (string) $parts->get('bugfix')
        );
    }
}
