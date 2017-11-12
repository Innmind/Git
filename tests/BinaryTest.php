<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Binary,
    Exception\CommandFailed
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    Server\Process\Output
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
    public function testSuccessfulInvokation()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git watev' &&
                    $command->workingDirectory() === '/tmp/foo' &&
                    $command->toBeRunInBackground() === false;
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));

        $bin = new Binary(
            $server,
            new Path('/tmp/foo')
        );

        $this->assertSame($output, $bin('watev'));
    }

    public function testThrowWhenCommandFailed()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git watev' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $bin = new Binary(
            $server,
            new Path('/tmp/foo')
        );

        try {
            $bin('watev');
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertSame($process, $e->process());
        }
    }
}
