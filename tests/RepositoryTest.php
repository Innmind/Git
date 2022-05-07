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
    Exception\RepositoryInitFailed,
    Exception\PathNotUsable,
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode,
    Server\Command\Str,
    ServerFactory
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class RepositoryTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        (new Filesystem)->remove('/tmp/foo');
    }

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
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "mkdir '-p' '/tmp/foo'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $this->expectException(PathNotUsable::class);
        $this->expectExceptionMessage('/tmp/foo');

        new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'init'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        try {
            $repo->init();
            $this->fail('it should throw');
        } catch (CommandFailed $e) {
            $this->assertSame("git 'init'", $e->command()->toString());
            $this->assertSame($process2, $e->process());
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'init'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process2
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
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
            ServerFactory::build(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertDirectoryDoesNotExist('/tmp/foo/.git');
        $this->assertNull($repo->init());
        $this->assertNull($repo->init()); //validate reinit doesn't throw
        $this->assertDirectoryExists('/tmp/foo/.git');
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'branch' '--no-color'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process2
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn($list);

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $head = $repo->head();

        $this->assertInstanceOf($class, $head);
        $this->assertSame($expected, $head->toString());
    }

    public function testBranches()
    {
        $repo = new Repository(
            ServerFactory::build(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'push'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertNull($repo->push());
    }

    public function testPull()
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
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'pull'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertNull($repo->pull());
    }

    public function testRemotes()
    {
        $repo = new Repository(
            ServerFactory::build(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertInstanceOf(Remotes::class, $repo->remotes());
    }

    public function testCheckout()
    {
        $repo = new Repository(
            ServerFactory::build(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertInstanceOf(Checkout::class, $repo->checkout());
    }

    public function testTags()
    {
        $repo = new Repository(
            ServerFactory::build(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'add' 'foo'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertNull($repo->add(Path::of('foo')));
    }

    public function testCommit()
    {
        $this
            ->forAll(Set\Strings::atLeast(1)->filter(
                static fn($string) => $string === \trim($string),
            ))
            ->then(function(string $message): void {
                $server = $this->createMock(Server::class);
                $server
                    ->expects($this->exactly(2))
                    ->method('processes')
                    ->willReturn($processes = $this->createMock(Processes::class));
                $processes
                    ->expects($this->exactly(2))
                    ->method('execute')
                    ->withConsecutive(
                        [],
                        [$this->callback(static function($command) use ($message): bool {
                            $message = (new Str($message))->toString();

                            return $command->toString() === "git 'commit' '-m' $message" &&
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
                $process2
                    ->expects($this->once())
                    ->method('wait');
                $process2
                    ->method('exitCode')
                    ->willReturn(new ExitCode(0));

                $repo = new Repository(
                    $server,
                    Path::of('/tmp/foo'),
                    $this->createMock(Clock::class),
                );

                $this->assertNull($repo->commit(new Message($message)));
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "git 'merge' 'develop'" &&
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
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $repo = new Repository(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertNull($repo->merge(new Branch('develop')));
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
