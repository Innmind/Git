<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Branches,
    Revision\Branch,
    Binary
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class BranchesTest extends TestCase
{
    public function testLocal()
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
                return $command->toString() === "git 'branch' '--no-color'" &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn(<<<BRANCHES
* (HEAD detached at aa4a336)
  develop
  foo-bar-baz
  master

BRANCHES
        );

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $local = $branches->local();

        $this->assertInstanceOf(Set::class, $local);
        $this->assertCount(3, $local);
        $local = $local->toList();
        $this->assertSame('develop', \current($local)->toString());
        \next($local);
        $this->assertSame('foo-bar-baz', \current($local)->toString());
        \next($local);
        $this->assertSame('master', \current($local)->toString());
    }

    public function testRemote()
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
                return $command->toString() === "git 'branch' '-r' '--no-color'" &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn(<<<BRANCHES
  origin/HEAD -> origin/master
  origin/develop
  origin/foo-bar-baz
  origin/master

BRANCHES
        );

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $remote = $branches->remote();

        $this->assertInstanceOf(Set::class, $remote);
        $this->assertCount(3, $remote);
        $remote = $remote->toList();
        $this->assertSame('origin/develop', \current($remote)->toString());
        \next($remote);
        $this->assertSame('origin/foo-bar-baz', \current($remote)->toString());
        \next($remote);
        $this->assertSame('origin/master', \current($remote)->toString());
    }

    public function testAll()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'branch' '--no-color'" &&
                        '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'branch' '-r' '--no-color'" &&
                        '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
                })],
            )
            ->will($this->onConsecutiveCalls(
                $process1 = $this->createMock(Process::class),
                $process2 = $this->createMock(Process::class),
            ));
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process1
            ->method('output')
            ->willReturn($output1 = $this->createMock(Output::class));
        $output1
            ->expects($this->once())
            ->method('toString')
            ->willReturn(<<<BRANCHES
* (HEAD detached at aa4a336)
  develop
  foo-bar-baz
  master
BRANCHES
        );
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->method('output')
            ->willReturn($output2 = $this->createMock(Output::class));
        $output2
            ->expects($this->once())
            ->method('toString')
            ->willReturn(<<<BRANCHES
  origin/HEAD -> origin/master
  origin/develop
  origin/foo-bar-baz
  origin/master
BRANCHES
        );

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $all = $branches->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(6, $all);
        $all = $all->toList();
        $this->assertSame('develop', \current($all)->toString());
        \next($all);
        $this->assertSame('foo-bar-baz', \current($all)->toString());
        \next($all);
        $this->assertSame('master', \current($all)->toString());
        \next($all);
        $this->assertSame('origin/develop', \current($all)->toString());
        \next($all);
        $this->assertSame('origin/foo-bar-baz', \current($all)->toString());
        \next($all);
        $this->assertSame('origin/master', \current($all)->toString());
    }

    public function testNew()
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
                return $command->toString() === "git 'branch' 'bar'" &&
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($branches->new(Branch::of('bar')));
    }

    public function testNewOff()
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
                return $command->toString() === "git 'branch' 'bar' 'develop'" &&
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($branches->new(Branch::of('bar'), Branch::of('develop')));
    }

    public function testNewOrphan()
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
                return $command->toString() === "git 'checkout' '--orphan' 'bar'" &&
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($branches->newOrphan(Branch::of('bar')));
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
                return $command->toString() === "git 'branch' '-d' 'bar'" &&
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($branches->delete(Branch::of('bar')));
    }

    public function testForceDelete()
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
                return $command->toString() === "git 'branch' '-D' 'bar'" &&
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($branches->forceDelete(Branch::of('bar')));
    }
}
