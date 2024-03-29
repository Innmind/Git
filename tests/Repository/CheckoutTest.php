<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Checkout,
    Revision\Branch,
    Revision\Hash,
    Binary
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    /**
     * @dataProvider paths
     */
    public function testFile(string $path)
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command) use ($path): bool {
                return $command->toString() === "git 'checkout' '--' '$path'" &&
                    '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $checkout = new Checkout(
            new Binary(
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

    /**
     * @dataProvider revisions
     */
    public function testRevision(Hash|Branch $revision)
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command) use ($revision): bool {
                return $command->toString() === "git 'checkout' '{$revision->toString()}'" &&
                    '/tmp/foo' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $checkout = new Checkout(
            new Binary(
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
