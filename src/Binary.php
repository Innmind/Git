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
    Attempt,
    Sequence,
};

/**
 * @internal
 */
final class Binary
{
    private Server $server;
    private Command $command;

    private function __construct(Server $server, Path $path, ?Path $home = null)
    {
        $this->server = $server;
        $this->command = Command::foreground('git')
            ->withWorkingDirectory($path);

        if ($home) {
            $this->command = $this->command->withEnvironment('HOME', $home->toString());
        }
    }

    /**
     * @return Attempt<Sequence<Chunk>>
     */
    #[\NoDiscard]
    public function __invoke(Command $command): Attempt
    {
        return $this
            ->server
            ->processes()
            ->execute($command)
            ->flatMap(
                static fn($process) => $process
                    ->wait()
                    ->attempt(static fn($error) => new \RuntimeException($error::class)),
            )
            ->map(static fn($success) => $success->output());
    }

    /**
     * @internal
     */
    public static function of(Server $server, Path $path, ?Path $home = null): self
    {
        return new self($server, $path, $home);
    }

    #[\NoDiscard]
    public function command(): Command
    {
        return $this->command;
    }
}
