<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Branches,
    Revision\Branch,
    Binary,
};
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    Attempt,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class BranchesTest extends TestCase
{
    public function testLocal()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' '--no-color'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(
                    Builder::foreground(2)
                        ->success([[
                            <<<BRANCHES
                            * (HEAD detached at aa4a336)
                              develop
                              foo-bar-baz
                              master

                            BRANCHES,
                            'output',
                        ]])
                        ->build(),
                );
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $local = $branches->local();

        $this->assertInstanceOf(Set::class, $local);
        $this->assertSame(3, $local->size());
        $local = $local->toList();
        $this->assertSame('develop', \current($local)->toString());
        \next($local);
        $this->assertSame('foo-bar-baz', \current($local)->toString());
        \next($local);
        $this->assertSame('master', \current($local)->toString());
    }

    public function testRemote()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' '-r' '--no-color'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(
                    Builder::foreground(2)
                        ->success([[
                            <<<BRANCHES
                              origin/HEAD -> origin/master
                              origin/develop
                              origin/foo-bar-baz
                              origin/master

                            BRANCHES,
                            'output',
                        ]])
                        ->build(),
                );
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $remote = $branches->remote();

        $this->assertInstanceOf(Set::class, $remote);
        $this->assertSame(3, $remote->size());
        $remote = $remote->toList();
        $this->assertSame('origin/develop', \current($remote)->toString());
        \next($remote);
        $this->assertSame('origin/foo-bar-baz', \current($remote)->toString());
        \next($remote);
        $this->assertSame('origin/master', \current($remote)->toString());
    }

    public function testAll()
    {
        $server = Server::via(
            function($command) {
                if ($command->toString() === "git 'branch' '--no-color'") {
                    $this->assertSame(
                        '/tmp/foo',
                        $command->workingDirectory()->match(
                            static fn($path) => $path->toString(),
                            static fn() => null,
                        ),
                    );

                    return Attempt::result(
                        Builder::foreground(2)
                            ->success([[
                                <<<BRANCHES
                                * (HEAD detached at aa4a336)
                                  develop
                                  foo-bar-baz
                                  master

                                BRANCHES,
                                'output',
                            ]])
                            ->build(),
                    );
                }

                $this->assertSame(
                    "git 'branch' '-r' '--no-color'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(
                    Builder::foreground(2)
                        ->success([[
                            <<<BRANCHES
                              origin/HEAD -> origin/master
                              origin/develop
                              origin/foo-bar-baz
                              origin/master

                            BRANCHES,
                            'output',
                        ]])
                        ->build(),
                );
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );
        $all = $branches->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(6, $all->size());
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
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' 'bar'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $branches->new(Branch::of('bar'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testNewOff()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' 'bar' 'develop'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $branches->new(Branch::of('bar'), Branch::of('develop'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testNewOrphan()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'checkout' '--orphan' 'bar'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $branches->newOrphan(Branch::of('bar'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testDelete()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' '-d' 'bar'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $branches->delete(Branch::of('bar'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testForceDelete()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'branch' '-D' 'bar'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $branches = Branches::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $branches->forceDelete(Branch::of('bar'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
