<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\Repository\{
    Remote,
    Remote\Name,
    Remote\Url
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode,
    ServerFactory
};
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testName()
    {
        $remote = new Remote(
            $this->createMock(Server::class),
            '/tmp/foo',
            $expected = new Name('origin')
        );

        $this->assertSame($expected, $remote->name());
    }

    public function testPrune()
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
                return (string) $command === 'git remote prune origin' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            $server,
            '/tmp/foo',
            new Name('origin')
        );

        $this->assertSame($remote, $remote->prune());
    }

    public function testSetUrl()
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
                return (string) $command === 'git remote set-url origin /local/remote' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            $server,
            '/tmp/foo',
            new Name('origin')
        );

        $this->assertSame($remote, $remote->setUrl(new Url('/local/remote')));
    }

    public function testAddUrl()
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
                return (string) $command === 'git remote set-url --add origin /local/remote' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            $server,
            '/tmp/foo',
            new Name('origin')
        );

        $this->assertSame($remote, $remote->addUrl(new Url('/local/remote')));
    }

    public function testRemoveUrl()
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
                return (string) $command === 'git remote set-url --delete origin /local/remote' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            $server,
            '/tmp/foo',
            new Name('origin')
        );

        $this->assertSame($remote, $remote->removeUrl(new Url('/local/remote')));
    }
}
