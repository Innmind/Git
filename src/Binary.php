<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Process\Output\Chunk,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Maybe,
    Sequence,
};

/**
 * @internal
 */
final class Binary
{
    private Server $server;
    private Command $command;

    public function __construct(Server $server, Path $path, ?Path $home = null)
    {
        $this->server = $server;
        $this->command = Command::foreground('git')
            ->withWorkingDirectory($path);

        if ($home) {
            $this->command = $this->command->withEnvironment('HOME', $home->toString());
        }
    }

    /**
     * @return Maybe<Sequence<Chunk>>
     */
    #[\NoDiscard]
    public function __invoke(Command $command): Maybe
    {
        return $this
            ->server
            ->processes()
            ->execute($command)
            ->unwrap()
            ->wait()
            ->maybe()
            ->map(static fn($success) => $success->output());
    }

    #[\NoDiscard]
    public function command(): Command
    {
        return $this->command;
    }
}
