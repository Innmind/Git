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
use Innmind\Server\Control\Servers\Mock;
use Innmind\Url\Path;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testName()
    {
        $remote = new Remote(
            new Binary(
                Mock::new($this->assert()),
                Path::of('/tmp/foo'),
            ),
            $expected = Name::of('origin'),
        );

        $this->assertSame($expected, $remote->name());
    }

    public function testPrune()
    {
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
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
            });

        $remote = new Remote(
            new Binary(
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
