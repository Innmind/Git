<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\{
    Revision\Hash,
    Revision\Branch,
    Repository\Branches,
    Repository\Remotes,
    Exception\CommandFailed,
    Exception\RepositoryInitFailed,
    Exception\PathNotUsable
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
    private $branches;
    private $remotes;

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

    public function head(): Revision
    {
        $output = new Str((string) $this->execute('branch --no-color'));
        $revision = $output
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return $line->matches('~^\* .+~');
            })
            ->first();

        if ($revision->matches('~\(HEAD detached at [a-z0-9]{7,40}\)~')) {
            return new Hash(
                (string) $revision
                    ->capture('~\(HEAD detached at (?P<hash>[a-z0-9]{7,40})\)~')
                    ->get('hash')
            );
        }

        return new Branch((string) $revision->substring(2));
    }

    public function branches(): Branches
    {
        return $this->branches ?? $this->branches = new Branches($this->server, $this->path);
    }

    public function push(): self
    {
        $this->execute('push');

        return $this;
    }

    public function pull(): self
    {
        $this->execute('pull');

        return $this;
    }

    public function remotes(): Remotes
    {
        return $this->remotes ?? $this->remotes = new Remotes($this->server, $this->path);
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
