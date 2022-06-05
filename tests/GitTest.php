<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\{
    Git,
    Repository,
    Version,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\Either;
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
        $git = Git::of(
            Factory::build()->control(),
            $this->createMock(Clock::class),
        );

        $this->assertInstanceOf(
            Repository::class,
            $git->repository(Path::of('/tmp/foo'))->match(
                static fn($repo) => $repo,
                static fn() => null,
            ),
        );
    }

    public function testVersion()
    {
        $git = Git::of(
            Factory::build()->control(),
            $this->createMock(Clock::class),
        );

        $this->assertInstanceOf(Version::class, $git->version()->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenFailToDetermineVersion()
    {
        $git = Git::of(
            $server = $this->createMock(Server::class),
            $this->createMock(Clock::class),
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git '--version'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(new ExitCode(1))));

        $this->assertNull($git->version()->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }
}
