<?php
declare(strict_types = 1);

namespace Innmind\Git\Exception;

use Innmind\Server\Control\Server\Process\Output;

final class RepositoryInitFailed extends RuntimeException
{
    private Output $output;

    public function __construct(Output $output)
    {
        parent::__construct();
        $this->output = $output;
    }

    public function output(): Output
    {
        return $this->output;
    }
}
