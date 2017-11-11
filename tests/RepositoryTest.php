<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Repository,
    Repository\Branches,
    Revision\Hash,
    Revision\Branch,
    Exception\CommandFailed,
    Exception\RepositoryInitFailed
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode,
    ServerFactory
};
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function setUp()
    {
        (new Filesystem)->remove('/tmp/foo');
    }

    /**
     * @expectedException Innmind\Git\Exception\PathNotUsable
     * @expectedExceptionMessage /tmp/foo
     */
    public function testThrowWhenDirectoryIsNotAccessible()
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
                return (string) $command === 'mkdir -p /tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        new Repository(
            $server,
            '/tmp/foo'
        );
    }

    public function testThrowWhenInitProcessFailed()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git init' &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn($exitCode = new ExitCode(1));

        $repo = new Repository(
            $server,
            '/tmp/foo'
        );

        try {
            $repo->init();
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertSame('init', $e->command());
            $this->assertSame($exitCode, $e->exitCode());
        }
    }

    public function testThrowWhenInitOutputIsNotAsExpected()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git init' &&
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

        $repo = new Repository(
            $server,
            '/tmp/foo'
        );

        try {
            $repo->init();
            $this->fail('it should throw');
        } catch (RepositoryInitFailed $e) {
            $this->assertSame($output, $e->output());
        }
    }

    public function testInit()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            '/tmp/foo'
        );

        $this->assertFalse(is_dir('/tmp/foo/.git'));
        $this->assertSame($repo, $repo->init());
        $this->assertSame($repo, $repo->init()); //validate reinit doesn't throw
        $this->assertTrue(is_dir('/tmp/foo/.git'));
    }

    /**
     * @dataProvider heads
     */
    public function testHead(string $list, string $expected, string $class)
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $processes
            ->expects($this->at(1))
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
            ->willReturn($list);

        $repo = new Repository(
            $server,
            '/tmp/foo'
        );

        $head = $repo->head();

        $this->assertInstanceOf($class, $head);
        $this->assertSame($expected, (string) $head);
    }

    public function testBranches()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            '/tmp/foo'
        );

        $this->assertInstanceOf(Branches::class, $repo->branches());
    }

    public function heads(): array
    {
        $detached = <<<DETACHED
* (HEAD detached at aa4a336)
  develop
  master
DETACHED;
        $branches = <<<BRANCHES
* develop
  master
BRANCHES;

        return [
            [$detached, 'aa4a336', Hash::class],
            [$branches, 'develop', Branch::class],
        ];
    }
}
