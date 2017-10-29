<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Git,
    Repository,
    Version
};
use Innmind\Server\Control\{
    ServerFactory,
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode
};
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class GitTest extends TestCase
{
    public function setUp()
    {
        (new Filesystem)->remove('/tmp/foo');
    }

    public function testRepository()
    {
        $git = new Git(
            (new ServerFactory)->make()
        );

        $this->assertInstanceOf(Repository::class, $git->repository('/tmp/foo'));
    }

    public function testVersion()
    {
        $git = new Git(
            (new ServerFactory)->make()
        );

        $this->assertInstanceOf(Version::class, $git->version());
    }

    /**
     * @expectedException Innmind\Git\Exception\CommandFailed
     */
    public function testThrowWhenFailToDetermineVersion()
    {
        $git = new Git(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === 'git --version';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $git->version();
    }
}
