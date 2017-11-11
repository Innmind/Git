<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Revision,
    Revision\Branch,
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

final class Branches
{
    private $server;
    private $path;

    public function __construct(Server $server, string $path)
    {
        $this->server = $server;
        $this->path = $path;
    }

    /**
     * @return SetInterface<Branch>
     */
    public function local(): SetInterface
    {
        $branches = new Str((string) $this->execute('branch --no-color'));

        return $branches
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->matches('~HEAD detached~');
            })
            ->reduce(
                new Set(Branch::class),
                static function(Set $branches, Str $branch): Set {
                    return $branches->add(new Branch(
                        (string) $branch->substring(2)
                    ));
                }
            );
    }

    /**
     * @return SetInterface<Branch>
     */
    public function remote(): SetInterface
    {
        $branches = new Str((string) $this->execute('branch -r --no-color'));

        return $branches
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return !$line->matches('~-> origin/~');
            })
            ->reduce(
                new Set(Branch::class),
                static function(Set $branches, Str $branch): Set {
                    return $branches->add(new Branch(
                        (string) $branch->substring(2)
                    ));
                }
            );
    }

    /**
     * @return SetInterface<Branch>
     */
    public function all(): SetInterface
    {
        return $this
            ->local()
            ->merge($this->remote());
    }

    public function new(Branch $name, Revision $off = null): self
    {
        $this->execute("branch $name $off");

        return $this;
    }

    public function delete(Branch $name): self
    {
        $this->execute("branch -d $name");

        return $this;
    }

    public function forceDelete(Branch $name): self
    {
        $this->execute("branch -D $name");

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
