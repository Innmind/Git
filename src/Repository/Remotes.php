<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Remote\Name,
    Repository\Remote\Url,
    Exception\CommandFailed
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str
};

final class Remotes
{
    private $server;
    private $path;

    public function __construct(Server $server, string $path)
    {
        $this->server = $server;
        $this->path = $path;
    }

    /**
     * @return SetInterface<Remote>
     */
    public function all(): SetInterface
    {
        $remotes = new Str((string) $this->execute('remote'));

        return $remotes
            ->split("\n")
            ->reduce(
                new Set(Remote::class),
                function(Set $remotes, Str $remote): Set {
                    return $remotes->add(
                        $this->get((string) $remote)
                    );
                }
            );
    }

    public function get(string $name): Remote
    {
        return new Remote(
            $this->server,
            $this->path,
            new Name($name)
        );
    }

    public function add(string $name, Url $url): Remote
    {
        $this->execute(sprintf(
            'remote add %s %s',
            new Name($name),
            $url
        ));

        return $this->get($name);
    }

    public function remove(string $name): self
    {
        $this->execute('remote remove '.new Name($name));

        return $this;
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
