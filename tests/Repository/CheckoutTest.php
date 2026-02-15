<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Checkout,
    Revision\Branch,
    Revision\Hash,
    Binary,
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
use PHPUnit\Framework\Attributes\DataProvider;

class CheckoutTest extends TestCase
{
    #[DataProvider('paths')]
    public function testFile(string $path)
    {
        $server = Server::via(
            function($command) use ($path) {
                $this->assertSame(
                    "git 'checkout' '--' '$path'",
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

        $checkout = Checkout::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $checkout->file(Path::of($path))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[DataProvider('revisions')]
    public function testRevision(Hash|Branch $revision)
    {
        $server = Server::via(
            function($command) use ($revision) {
                $this->assertSame(
                    "git 'checkout' '{$revision->toString()}'",
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

        $checkout = Checkout::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $checkout->revision($revision)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public static function paths(): array
    {
        return [
            ['some/relative/file.txt'],
            ['/absolute/file.txt'],
            ['.'],
            ['everything/under/name*'],
        ];
    }

    public static function revisions(): array
    {
        return [
            [Branch::of('master')],
            [Hash::maybe('h2g2a42')->match(
                static fn($hash) => $hash,
                static fn() => null,
            )],
        ];
    }
}
