<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\Repository\Remote\{
    Name,
    Url
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output
};

final class Remote
{
    private $server;
    private $path;
    private $name;

    public function __construct(Server $server, string $path, Name $name)
    {
        $this->server = $server;
        $this->path = $path;
        $this->name = $name;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function prune(): self
    {
        $this->execute('remote prune '.$this->name);

        return $this;
    }

    public function setUrl(Url $url): self
    {
        $this->execute(sprintf(
            'remote set-url %s %s',
            $this->name,
            $url
        ));

        return $this;
    }

    public function addUrl(Url $url): self
    {
        $this->execute(sprintf(
            'remote set-url --add %s %s',
            $this->name,
            $url
        ));

        return $this;
    }

    public function removeUrl(Url $url): self
    {
        $this->execute(sprintf(
            'remote set-url --delete %s %s',
            $this->name,
            $url
        ));

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
