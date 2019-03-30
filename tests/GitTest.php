<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Git,
    Repository,
    Version,
    Exception\CommandFailed,
};
use Innmind\Server\Control\{
    ServerFactory,
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class GitTest extends TestCase
{
    public function setUp(): void
    {
        (new Filesystem)->remove('/tmp/foo');
    }

    public function testRepository()
    {
        $git = new Git(
            (new ServerFactory)->make(),
            $this->createMock(TimeContinuumInterface::class)
        );

        $this->assertInstanceOf(Repository::class, $git->repository(new Path('/tmp/foo')));
    }

    public function testVersion()
    {
        $git = new Git(
            (new ServerFactory)->make(),
            $this->createMock(TimeContinuumInterface::class)
        );

        $this->assertInstanceOf(Version::class, $git->version());
    }

    public function testThrowWhenFailToDetermineVersion()
    {
        $git = new Git(
            $server = $this->createMock(Server::class),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === "git '--version'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $this->expectException(CommandFailed::class);

        $git->version();
    }
}
