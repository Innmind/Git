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
    Server\Command\Str,
    Server,
    Server\Process\Builder,
};
use Innmind\Url\Path;
use Innmind\Time\Clock;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Symfony\Component\Filesystem\Filesystem;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\DataProvider;

class RepositoryTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        (new Filesystem)->remove('/tmp/foo');
    }

    public function testReturnNothingWhenDirectoryIsNotAccessible()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "mkdir '-p' '/tmp/foo'",
                    $command->toString(),
                );

                return Attempt::result(
                    Builder::foreground(2)
                        ->failed()
                        ->build(),
                );
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
        );

        $this->assertNull($repo->match(
            static fn($repo) => $repo,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenInitProcessFailed()
    {
        $server = Server::via(
            function($command) {
                if ($command->toString() !== "git 'init'") {
                    return Attempt::result(Builder::foreground(2)->build());
                }

                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));

                return Attempt::result(
                    Builder::foreground(2)
                        ->failed()
                        ->build(),
                );
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
        $server = Server::via(
            function($command) {
                if ($command->toString() !== "git 'init'") {
                    return Attempt::result(Builder::foreground(2)->build());
                }

                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));

                return Attempt::result(Builder::foreground(2)->build(),
                );
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
            Clock::live(),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertFalse(\file_exists('/tmp/foo/.git'));
        $this->assertInstanceOf(SideEffect::class, $repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
        $this->assertInstanceOf(SideEffect::class, $repo->init()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        )); //validate reinit doesn't throw
        $this->assertTrue(\file_exists('/tmp/foo/.git'));
        $this->assertTrue(\is_dir('/tmp/foo/.git'));
    }

    #[DataProvider('heads')]
    public function testHead(string $list, string $expected, string $class)
    {
        $server = Server::via(
            function($command) use ($list) {
                if ($command->toString() !== "git 'branch' '--no-color'") {
                    return Attempt::result(Builder::foreground(2)->build());
                }

                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));

                return Attempt::result(
                    Builder::foreground(2)
                        ->success([[$list, 'output']])
                        ->build(),
                );
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
            Clock::live(),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(Branches::class, $repo->branches());
    }

    public function testPush()
    {
        $server = Server::via(
            function($command) {
                if ($command->toString() === "git 'push'") {
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
        $server = Server::via(
            function($command) {
                if ($command->toString() === "git 'pull'") {
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
            Clock::live(),
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
            Clock::live(),
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
            Clock::live(),
        )->match(
            static fn($repo) => $repo,
            static fn() => null,
        );

        $this->assertInstanceOf(Tags::class, $repo->tags());
    }

    public function testAdd()
    {
        $server = Server::via(
            function($command) {
                if ($command->toString() === "git 'add' 'foo'") {
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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

    public function testCommit(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->atLeast(1)->filter(
                static fn($string) => $string === \trim($string),
            ))
            ->prove(function(string $message): void {
                $messageArgument = Str::escape($message);
                $server = Server::via(
                    function($command) use ($messageArgument) {
                        if ($command->toString() === "git 'commit' '-m' $messageArgument") {
                            $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                                static fn($path) => $path->toString(),
                                static fn() => null,
                            ));
                        }

                        return Attempt::result(Builder::foreground(2)->build());
                    },
                );

                $repo = Repository::of(
                    $server,
                    Path::of('/tmp/foo'),
                    Clock::live(),
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
        $server = Server::via(
            function($command) {
                if ($command->toString() === "git 'merge' 'develop'") {
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                }

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $repo = Repository::of(
            $server,
            Path::of('/tmp/foo'),
            Clock::live(),
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
