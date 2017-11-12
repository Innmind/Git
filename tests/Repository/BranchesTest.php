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
use Innmind\Immutable\SetInterface;
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch --no-color' &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
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
                new Path('/tmp/foo')
            )
        );
        $local = $branches->local();

        $this->assertInstanceOf(SetInterface::class, $local);
        $this->assertSame(Branch::class, (string) $local->type());
        $this->assertCount(3, $local);
        $this->assertSame('develop', (string) $local->current());
        $local->next();
        $this->assertSame('foo-bar-baz', (string) $local->current());
        $local->next();
        $this->assertSame('master', (string) $local->current());
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch -r --no-color' &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
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
                new Path('/tmp/foo')
            )
        );
        $remote = $branches->remote();

        $this->assertInstanceOf(SetInterface::class, $remote);
        $this->assertSame(Branch::class, (string) $remote->type());
        $this->assertCount(3, $remote);
        $this->assertSame('origin/develop', (string) $remote->current());
        $remote->next();
        $this->assertSame('origin/foo-bar-baz', (string) $remote->current());
        $remote->next();
        $this->assertSame('origin/master', (string) $remote->current());
    }

    public function testAll()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch --no-color' &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn(<<<BRANCHES
* (HEAD detached at aa4a336)
  develop
  foo-bar-baz
  master
BRANCHES
        );

        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch -r --no-color' &&
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
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
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
                new Path('/tmp/foo')
            )
        );
        $all = $branches->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Branch::class, (string) $all->type());
        $this->assertCount(6, $all);
        $this->assertSame('develop', (string) $all->current());
        $all->next();
        $this->assertSame('foo-bar-baz', (string) $all->current());
        $all->next();
        $this->assertSame('master', (string) $all->current());
        $all->next();
        $this->assertSame('origin/develop', (string) $all->current());
        $all->next();
        $this->assertSame('origin/foo-bar-baz', (string) $all->current());
        $all->next();
        $this->assertSame('origin/master', (string) $all->current());
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch bar' &&
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

        $branches = new Branches(
            new Binary(
                $server,
                new Path('/tmp/foo')
            )
        );

        $this->assertSame($branches, $branches->new(new Branch('bar')));
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch bar develop' &&
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

        $branches = new Branches(
            new Binary(
                $server,
                new Path('/tmp/foo')
            )
        );

        $this->assertSame($branches, $branches->new(new Branch('bar'), new Branch('develop')));
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch -d bar' &&
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

        $branches = new Branches(
            new Binary(
                $server,
                new Path('/tmp/foo')
            )
        );

        $this->assertSame($branches, $branches->delete(new Branch('bar')));
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
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git branch -D bar' &&
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

        $branches = new Branches(
            new Binary(
                $server,
                new Path('/tmp/foo')
            )
        );

        $this->assertSame($branches, $branches->forceDelete(new Branch('bar')));
    }
}
