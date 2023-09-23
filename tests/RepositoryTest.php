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
};
use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode,
    Server\Command\Str,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
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

    public function testReturnNothingWhenDirectoryIsNotAccessible()
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
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                $this->createMock(Output::class),
            )));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        );

        $this->assertNull($repo->match(
            static fn($repo) => $repo,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenInitProcessFailed()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'init'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                $this->createMock(Output::class),
            )));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertNull($repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenInitOutputIsNotAsExpected()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'init'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertNull($repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
    }

    public function testInit()
    {
        $repo = Repository::of(
            Factory::build()->control(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertDirectoryDoesNotExist('/tmp/foo/.git');
        $this->assertInstanceOf(SideEffect::class, $repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
        $this->assertInstanceOf(SideEffect::class, $repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        )); //validate reinit doesn't throw
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
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'branch' '--no-color'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn($list);

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $head = $repo->head()->match(
            static fn($head) => $head,
            static fn() => null,
        );

        $this->assertInstanceOf($class, $head);
        $this->assertSame($expected, $head->toString());
    }

    public function testBranches()
    {
        $repo = Repository::of(
            Factory::build()->control(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
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
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'push'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $repo->push()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testPull()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'pull'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $repo->pull()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testRemotes()
    {
        $repo = Repository::of(
            Factory::build()->control(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(Remotes::class, $repo->remotes());
    }

    public function testCheckout()
    {
        $repo = Repository::of(
            Factory::build()->control(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(Checkout::class, $repo->checkout());
    }

    public function testTags()
    {
        $repo = Repository::of(
            Factory::build()->control(),
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
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
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'add' 'foo'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $repo->add(Path::of('foo'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
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
                $process1 = $this->createMock(Process::class);
                $process2 = $this->createMock(Process::class);
                $processes
                    ->expects($matcher = $this->exactly(2))
                    ->method('execute')
                    ->willReturnCallback(function($command) use ($matcher, $message, $process1, $process2) {
                        $message = (new Str($message))->toString();

                        if ($matcher->numberOfInvocations() === 2) {
                            $this->assertSame("git 'commit' '-m' $message", $command->toString());
                            $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                                static fn($path) => $path->toString(),
                                static fn() => null,
                            ));
                        }

                        return match ($matcher->numberOfInvocations()) {
                            1 => $process1,
                            2 => $process2,
                        };
                    });
                $process1
                    ->expects($this->once())
                    ->method('wait')
                    ->willReturn(Either::right(new SideEffect));
                $process2
                    ->expects($this->once())
                    ->method('wait')
                    ->willReturn(Either::right(new SideEffect));

                $repo = Repository::of(
                    $server,
                    Path::of('/tmp/foo'),
                    $this->createMock(Clock::class),
                )->match(
                    static fn($repo) => $repo,
                    static fn() => null,
                );

                $this->assertInstanceOf(
                    SideEffect::class,
                    $repo->commit(Message::of($message))->match(
                        static fn($sideEffect) => $sideEffect,
                        static fn() => null,
                    ),
                );
            });
    }

    public function testMerge()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $process1, $process2) {
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame("git 'merge' 'develop'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return match ($matcher->numberOfInvocations()) {
                    1 => $process1,
                    2 => $process2,
                };
            });
        $process1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            $this->createMock(Clock::class),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $repo->merge(Branch::of('develop'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public static function heads(): array
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
