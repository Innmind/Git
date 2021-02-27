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
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
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
                Path::of('/tmp/foo')
            )
        );
        $local = $branches->local();

        $this->assertInstanceOf(Set::class, $local);
        $this->assertSame(Branch::class, (string) $local->type());
        $this->assertCount(3, $local);
        $local = unwrap($local);
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
                Path::of('/tmp/foo')
            )
        );
        $remote = $branches->remote();

        $this->assertInstanceOf(Set::class, $remote);
        $this->assertSame(Branch::class, (string) $remote->type());
        $this->assertCount(3, $remote);
        $remote = unwrap($remote);
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
                        $command->workingDirectory()->toString() === '/tmp/foo';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'branch' '-r' '--no-color'" &&
                        $command->workingDirectory()->toString() === '/tmp/foo';
                })],
            )
            ->will($this->onConsecutiveCalls(
                $process1 = $this->createMock(Process::class),
                $process2 = $this->createMock(Process::class),
            ));
        $process1
            ->expects($this->once())
            ->method('wait');
        $process1
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
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
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
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
                Path::of('/tmp/foo')
            )
        );
        $all = $branches->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Branch::class, (string) $all->type());
        $this->assertCount(6, $all);
        $all = unwrap($all);
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            )
        );

        $this->assertNull($branches->new(new Branch('bar')));
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            )
        );

        $this->assertNull($branches->new(new Branch('bar'), new Branch('develop')));
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
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            )
        );

        $this->assertNull($branches->newOrphan(new Branch('bar')));
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            )
        );

        $this->assertNull($branches->delete(new Branch('bar')));
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

        $branches = new Branches(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            )
        );

        $this->assertNull($branches->forceDelete(new Branch('bar')));
    }
}
