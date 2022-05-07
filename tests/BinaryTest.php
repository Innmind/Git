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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'watev'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo' &&
                    $command->toBeRunInBackground() === false;
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
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
            Path::of('/tmp/foo'),
        );

        $this->assertInstanceOf(Command::class, $bin->command());
        $this->assertSame('git', $bin->command()->toString());
        $this->assertSame('/tmp/foo', $bin->command()->workingDirectory()->toString());
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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'watev'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $bin = new Binary(
            $server,
            Path::of('/tmp/foo'),
        );

        try {
            $bin($bin->command()->withArgument('watev'));
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertInstanceOf(Command::class, $e->command());
            $this->assertSame("git 'watev'", $e->command()->toString());
            $this->assertSame($process, $e->process());
        }
    }
}
