<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Remotes,
    Repository\Remote,
    Repository\Remote\Name,
    Repository\Remote\Url,
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

class RemotesTest extends TestCase
{
    public function testAll()
    {
        $server = Server::via(
            static fn() => Attempt::result(
                Builder::foreground(2)
                    ->success([[
                        <<<REMOTES
                        origin
                        gitlab
                        local
                        REMOTES,
                        'output',
                    ]])
                    ->build(),
            ),
        );

        $remotes = Remotes::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $all = $remotes->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(3, $all->size());
        $all = $all->toList();
        $this->assertSame('origin', \current($all)->name()->toString());
        \next($all);
        $this->assertSame('gitlab', \current($all)->name()->toString());
        \next($all);
        $this->assertSame('local', \current($all)->name()->toString());
    }

    public function testGet()
    {
        $remotes = Remotes::of(
            Binary::of(
                Server::via(static fn() => null),
                Path::of('watev'),
            ),
        );

        $remote = $remotes->get($expected = Name::of('origin'));

        $this->assertInstanceOf(Remote::class, $remote);
        $this->assertSame($expected, $remote->name());
    }

    public function testAdd()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'add' 'origin' 'git@github.com:Innmind/Git.git'",
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

        $remotes = Remotes::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $remote = $remotes->add(
            $expected = Name::of('origin'),
            Url::of('git@github.com:Innmind/Git.git'),
        )->unwrap();

        $this->assertInstanceOf(Remote::class, $remote);
        $this->assertSame($expected, $remote->name());
    }

    public function testRemove()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'remote' 'remove' 'origin'",
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

        $remotes = Remotes::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remotes->remove(Name::of('origin'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
