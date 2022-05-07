<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Checkout,
    Revision\Branch,
    Revision\Hash,
    Revision,
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
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $checkout = new Checkout(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($checkout->file(Path::of($path)));
    }

    /**
     * @dataProvider revisions
     */
    public function testRevision(Revision $revision)
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
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $checkout = new Checkout(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($checkout->revision($revision));
    }

    public function paths(): array
    {
        return [
            ['some/relative/file.txt'],
            ['/absolute/file.txt'],
            ['.'],
            ['everything/under/name*'],
        ];
    }

    public function revisions(): array
    {
        return [
            [new Branch('master')],
            [new Hash('h2g2a42')],
        ];
    }
}
