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
    Server\Process\Output,
    Server\Command
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

        $this->assertInstanceOf(Command::class, $bin->command());
        $this->assertSame('git', (string) $bin->command());
        $this->assertSame('/tmp/foo', $bin->command()->workingDirectory());
        $this->assertSame($output, $bin($bin->command()->withArgument('watev')));
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
            $bin($bin->command()->withArgument('watev'));
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertInstanceOf(Command::class, $e->command());
            $this->assertSame('git watev', (string) $e->command());
            $this->assertSame($process, $e->process());
        }
    }
}
