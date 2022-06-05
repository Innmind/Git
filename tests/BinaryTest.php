<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\Binary;
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    Server\Process\Output,
    Server\Command
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
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
                    $command->toBeRunInBackground() === false &&
                    '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
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
        $this->assertSame('/tmp/foo', $bin->command()->workingDirectory()->match(
            static fn($path) => $path->toString(),
            static fn() => null,
        ));
        $this->assertSame($output, $bin($bin->command()->withArgument('watev'))->match(
            static fn($output) => $output,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenCommandFailed()
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
                    '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(new ExitCode(1))));

        $bin = new Binary(
            $server,
            Path::of('/tmp/foo'),
        );

        $this->assertNull($bin($bin->command()->withArgument('watev'))->match(
            static fn($output) => $output,
            static fn() => null,
        ));
    }
}
