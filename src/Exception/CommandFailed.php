<?php
declare(strict_types = 1);

namespace Innmind\Git\Exception;

use Innmind\Server\Control\Server\Process;

final class CommandFailed extends RuntimeException
{
    private $command;
    private $process;

    public function __construct(string $command, Process $process)
    {
        parent::__construct();
        $this->command = $command;
        $this->process = $process;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function process(): Process
    {
        return $this->process;
    }
}
