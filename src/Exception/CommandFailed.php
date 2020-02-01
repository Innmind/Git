<?php
declare(strict_types = 1);

namespace Innmind\Git\Exception;

use Innmind\Server\Control\Server\{
    Process,
    Command
};

final class CommandFailed extends RuntimeException
{
    private Command $command;
    private Process $process;

    public function __construct(Command $command, Process $process)
    {
        parent::__construct($command->toString());
        $this->command = $command;
        $this->process = $process;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function process(): Process
    {
        return $this->process;
    }
}
