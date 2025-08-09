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
    Servers\Mock,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\SideEffect;
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
        $server = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame(
                    "mkdir '-p' '/tmp/foo'",
                    $command->toString(),
                ),
                static fn($_, $builder) => $builder->failed(),
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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(
                function($command) {
                    $this->assertSame("git 'init'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                },
                static fn($_, $builder) => $builder->failed(),
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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(function($command) {
                $this->assertSame("git 'init'", $command->toString());
                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));
            });

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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(
                function($command) {
                    $this->assertSame("git 'branch' '--no-color'", $command->toString());
                    $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ));
                },
                static fn($_, $builder) => $builder->success([
                    [$list, 'output'],
                ]),
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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(function($command) {
                $this->assertSame("git 'push'", $command->toString());
                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));
            });

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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(function($command) {
                $this->assertSame("git 'pull'", $command->toString());
                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));
            });

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
        $server = Mock::new($this->assert())
            ->willExecute(static fn() => null)
            ->willExecute(function($command) {
                $this->assertSame("git 'add' 'foo'", $command->toString());
                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));
            });

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

    public function testCommit()
    {
        $this
            ->forAll(Set::strings()->atLeast(1)->filter(
                static fn($string) => $string === \trim($string),
            ))
            ->then(function(string $message): void {
                $messageArgument = (new Str($message))->toString();
                $server = Mock::new($this->assert())
                    ->willExecute(static fn() => null)
                    ->willExecute(function($command) use ($messageArgument) {
                        $this->assertSame("git 'commit' '-m' $messageArgument", $command->toString());
                        $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                            static fn($path) => $path->toString(),
                            static fn() => null,
                        ));
                    });

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
        $server = Mock::new($this->assert())
            ->willExecute(static fn($command) => null)
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'merge' 'develop'",
                    $command->toString(),
                );
                $this->assertSame('/tmp/foo', $command->workingDirectory()->match(
                    static fn($path) => $path->toString(),
                    static fn() => null,
                ));
            });

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
