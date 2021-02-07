<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Remote,
    Repository\Remote\Name,
    Repository\Remote\Url,
    Binary,
    Revision\Branch
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode,
    ServerFactory
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testName()
    {
        $remote = new Remote(
            new Binary(
                $this->createMock(Server::class),
                Path::of('/tmp/foo')
            ),
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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'remote' 'prune' 'origin'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->prune());
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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'remote' 'set-url' 'origin' '/local/remote'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->setUrl(new Url('/local/remote')));
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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'remote' 'set-url' '--add' 'origin' '/local/remote'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->addUrl(new Url('/local/remote')));
    }

    public function testDeleteUrl()
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
                return $command->toString() === "git 'remote' 'set-url' '--delete' 'origin' '/local/remote'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->deleteUrl(new Url('/local/remote')));
    }

    public function testPush()
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
                return $command->toString() === "git 'push' '-u' 'origin' 'develop'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->push(new Branch('develop')));
    }

    public function testDelete()
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
                return $command->toString() === "git 'push' 'origin' ':develop'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Name('origin')
        );

        $this->assertNull($remote->delete(new Branch('develop')));
    }
}
