<?php
declare(strict_types = 1);

namespace Innmind\Git\Exception;

use Innmind\Server\Control\Server\{
    Process,
    Command
};

final class CommandFailed extends RuntimeException
{
    private $command;
    private $process;

    public function __construct(Command $command, Process $process)
    {
        parent::__construct((string) $command);
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
