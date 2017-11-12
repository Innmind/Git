<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\CommandFailed;
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output
};
use Innmind\Url\PathInterface;

final class Binary
{
    private $server;
    private $command;

    public function __construct(Server $server, PathInterface $path)
    {
        $this->server = $server;
        $this->command = Command::foreground('git')
            ->withWorkingDirectory((string) $path);
    }

    public function __invoke(string $command): Output
    {
        $process = $this
            ->server
            ->processes()
            ->execute(
                $this->command->withArgument($command)
            )
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            throw new CommandFailed($command, $process);
        }

        return $process->output();
    }
}
