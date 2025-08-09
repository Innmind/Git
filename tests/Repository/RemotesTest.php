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
use Innmind\Server\Control\Servers\Mock;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class RemotesTest extends TestCase
{
    public function testAll()
    {
        $server = Mock::new($this->assert())
            ->willExecute(
                static fn() => null,
                static fn($_, $builder) => $builder->success([[
                    <<<REMOTES
                    origin
                    gitlab
                    local
                    REMOTES,
                    'output',
                ]]),
            );

        $remotes = Remotes::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $all = $remotes->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(3, $all);
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
                Mock::new($this->assert()),
                Path::of('watev'),
            ),
        );

        $remote = $remotes->get($expected = Name::of('origin'));

        $this->assertInstanceOf(Remote::class, $remote);
        $this->assertSame($expected, $remote->name());
    }

    public function testAdd()
    {
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

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
