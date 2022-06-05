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
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testName()
    {
        $remote = new Remote(
            new Binary(
                $this->createMock(Server::class),
                Path::of('/tmp/foo'),
            ),
            $expected = Name::of('origin'),
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
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
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
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertNull($remote->setUrl(Url::of('/local/remote')));
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
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertNull($remote->addUrl(Url::of('/local/remote')));
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
            ->method('output')
            ->willReturn($this->createMock(Output::class));

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertNull($remote->deleteUrl(Url::of('/local/remote')));
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

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertNull($remote->push(Branch::of('develop')));
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

        $remote = new Remote(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertNull($remote->delete(Branch::of('develop')));
    }
}
