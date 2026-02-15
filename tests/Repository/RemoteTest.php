<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Remote,
    Repository\Remote\Name,
    Repository\Remote\Url,
    Binary,
    Revision\Branch,
};
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testName()
    {
        $remote = Remote::of(
            Binary::of(
                Server::via(static fn() => null),
                Path::of('/tmp/foo'),
            ),
            $expected = Name::of('origin'),
        );

        $this->assertSame($expected, $remote->name());
    }

    public function testPrune()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'prune' 'origin'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->prune()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testSetUrl()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'set-url' 'origin' '/local/remote'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->setUrl(Url::of('/local/remote'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testAddUrl()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'set-url' '--add' 'origin' '/local/remote'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->addUrl(Url::of('/local/remote'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testDeleteUrl()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'set-url' '--delete' 'origin' '/local/remote'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->deleteUrl(Url::of('/local/remote'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testPush()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'push' '-u' 'origin' 'develop'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->push(Branch::of('develop'))->match(
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
                    "git 'push' 'origin' ':develop'",
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

        $remote = Remote::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Name::of('origin'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->delete(Branch::of('develop'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
