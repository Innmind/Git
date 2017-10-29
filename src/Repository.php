<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\Exception\{
    CommandFailed,
    RepositoryInitFailed,
    PathNotUsable
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output
};
use Innmind\Immutable\Str;

final class Repository
{
    private $server;
    private $path;

    public function __construct(Server $server, string $path)
    {
        $this->server = $server;
        $this->path = $path;

        $code = $server
            ->processes()
            ->execute(
                Command::foreground('mkdir')
                    ->withShortOption('p')
                    ->withArgument($path)
            )
            ->wait()
            ->exitCode();

        if (!$code->isSuccessful()) {
            throw new PathNotUsable($path);
        }
    }

    public function init(): self
    {
        $output = $this->execute('init');
        $outputStr = new Str((string) $output);

        if (
            $outputStr->contains('Initialized empty Git repository') ||
            $outputStr->contains('Reinitialized existing Git repository')
        ) {
            return $this;
        }

        throw new RepositoryInitFailed($output);
    }

    private function execute(string $command): Output
    {
        $process = $this
            ->server
            ->processes()
            ->execute(
                Command::foreground('git')
                    ->withWorkingDirectory($this->path)
                    ->withArgument($command)
            )
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            throw new CommandFailed($command, $process->exitCode());
        }

        return $process->output();
    }
}
