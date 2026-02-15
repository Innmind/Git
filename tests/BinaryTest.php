<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\Binary;
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\Url\Path;
use Innmind\Immutable\Attempt;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
    public function testSuccessfulInvokation()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'watev'",
                    $command->toString(),
                );
                $this->assertFalse($command->toBeRunInBackground());
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

        $bin = Binary::of(
            $server,
            Path::of('/tmp/foo'),
        );

        $this->assertSame(
            0,
            $bin(static fn($command) => $command->withArgument('watev'))->match(
                static fn($output) => $output->size(),
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenCommandFailed()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'watev'",
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
                        ->failed()
                        ->build(),
                );
            },
        );

        $bin = Binary::of(
            $server,
            Path::of('/tmp/foo'),
        );

        $this->assertNull($bin(static fn($command) => $command->withArgument('watev'))->match(
            static fn($output) => $output,
            static fn() => null,
        ));
    }

    public function testHomeIsAddedToTheCommandEnvironment()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "git 'watev'",
                    $command->toString(),
                );
                $this->assertFalse($command->toBeRunInBackground());
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    '/Users/baptouuuu',
                    $command->environment()->get('HOME')->match(
                        static fn($home) => $home,
                        static fn() => null,
                    ),
                );

                return Attempt::result(Builder::foreground(2)->build());
            },
        );

        $bin = Binary::of(
            $server,
            Path::of('/tmp/foo'),
            Path::of('/Users/baptouuuu'),
        );

        $this->assertSame(
            0,
            $bin(static fn($command) => $command->withArgument('watev'))->match(
                static fn($output) => $output->size(),
                static fn() => null,
            ),
        );
    }
}
