<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Repository,
    Repository\Branches,
    Repository\Remotes,
    Repository\Checkout,
    Repository\Tags,
    Revision\Hash,
    Revision\Branch,
    Message,
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
use Innmind\Url\Path;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class RepositoryTest extends TestCase
{
    use TestTrait;

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
            new Path('/tmp/foo')
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
            ->willReturn(new ExitCode(1));

        $repo = new Repository(
            $server,
            new Path('/tmp/foo')
        );

        try {
            $repo->init();
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertSame('init', $e->command());
            $this->assertSame($process, $e->process());
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
            new Path('/tmp/foo')
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
            new Path('/tmp/foo')
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
            new Path('/tmp/foo')
        );

        $head = $repo->head();

        $this->assertInstanceOf($class, $head);
        $this->assertSame($expected, (string) $head);
    }

    public function testBranches()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            new Path('/tmp/foo')
        );

        $this->assertInstanceOf(Branches::class, $repo->branches());
    }

    public function testPush()
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
                return (string) $command === 'git push' &&
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

        $repo = new Repository(
            $server,
            new Path('/tmp/foo')
        );

        $this->assertSame($repo, $repo->push());
    }

    public function testPull()
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
                return (string) $command === 'git pull' &&
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

        $repo = new Repository(
            $server,
            new Path('/tmp/foo')
        );

        $this->assertSame($repo, $repo->pull());
    }

    public function testRemotes()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            new Path('/tmp/foo')
        );

        $this->assertInstanceOf(Remotes::class, $repo->remotes());
    }

    public function testCheckout()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            new Path('/tmp/foo')
        );

        $this->assertInstanceOf(Checkout::class, $repo->checkout());
    }

    public function testTags()
    {
        $repo = new Repository(
            (new ServerFactory)->make(),
            new Path('/tmp/foo')
        );

        $this->assertInstanceOf(Tags::class, $repo->tags());
    }

    public function testAdd()
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
                return (string) $command === 'git add foo' &&
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

        $repo = new Repository(
            $server,
            new Path('/tmp/foo')
        );

        $this->assertSame($repo, $repo->add(new Path('foo')));
    }

    public function testCommit()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $message): bool {
                return strlen($message) > 0;
            })
            ->then(function(string $message): void {
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
                    ->with($this->callback(function($command) use ($message): bool {
                        return (string) $command === "git commit -m '$message'" &&
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

                $repo = new Repository(
                    $server,
                    new Path('/tmp/foo')
                );

                $this->assertSame($repo, $repo->commit(new Message($message)));
            });
    }

    public function testMerge()
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
                return (string) $command === 'git merge develop' &&
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

        $repo = new Repository(
            $server,
            new Path('/tmp/foo')
        );

        $this->assertSame($repo, $repo->merge(new Branch('develop')));
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
