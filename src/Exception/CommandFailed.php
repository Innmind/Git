<?php
declare(strict_types = 1);

namespace Innmind\Git\Exception;

use Innmind\Server\Control\Server\Process\ExitCode;

final class CommandFailed extends RuntimeException
{
    private $command;
    private $exitCode;

    public function __construct(string $command, ExitCode $exitCode)
    {
        parent::__construct();
        $this->command = $command;
        $this->exitCode = $exitCode;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function exitCode(): ExitCode
    {
        return $this->exitCode;
    }
}
