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
    Server\Process\Builder,
};
use Innmind\Url\Path;
use Innmind\Time\Clock;
use Innmind\Immutable\Attempt;
use Symfony\Component\Filesystem\Filesystem;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

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
            Clock::live(),
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
            Clock::live(),
        );

        $this->assertInstanceOf(Version::class, $git->version()->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenFailToDetermineVersion()
    {
        $git = Git::of(
            Server::via(
                function($command) {
                    $this->assertSame(
                        "git '--version'",
                        $command->toString(),
                    );

                    return Attempt::result(
                        Builder::foreground(2)
                            ->failed()
                            ->build(),
                    );
                },
            ),
            Clock::live(),
        );

        $this->assertNull($git->version()->match(
            static fn($version) => $version,
            static fn() => null,
        ));
    }
}
