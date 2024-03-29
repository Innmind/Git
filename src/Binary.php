<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output,
};
use Innmind\Url\Path;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class Binary
{
    private Server $server;
    private Command $command;

    public function __construct(Server $server, Path $path, Path $home = null)
    {
        $this->server = $server;
        $this->command = Command::foreground('git')
            ->withWorkingDirectory($path);

        if ($home) {
            $this->command = $this->command->withEnvironment('HOME', $home->toString());
        }
    }

    /**
     * @return Maybe<Output>
     */
    public function __invoke(Command $command): Maybe
    {
        $process = $this
            ->server
            ->processes()
            ->execute($command);

        /** @var Maybe<Output> */
        return $process
            ->wait()
            ->match(
                static fn() => Maybe::just($process->output()),
                static fn() => Maybe::nothing(),
            );
    }

    public function command(): Command
    {
        return $this->command;
    }
}
