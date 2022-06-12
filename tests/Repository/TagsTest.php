<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tags,
    Repository\Tag,
    Binary,
    Message,
    Repository\Tag\Name
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\Output,
    Server\Process\ExitCode
};
use Innmind\Url\Path;
use Innmind\TimeContinuum\Earth\{
    Clock,
    Format\ISO8601,
    Timezone\UTC,
};
use Innmind\Immutable\{
    Set,
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class TagsTest extends TestCase
{
    public function testPush()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'push' '--tags'" &&
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

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $tags->push()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testAdd()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'tag' '1.0.0' '-a' '-m' 'first release'" &&
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

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $tags->add(Name::of('1.0.0'), Message::of('first release'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testAddWithoutMessage()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'tag' '1.0.0'" &&
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

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $tags->add(Name::of('1.0.0'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testSign()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'tag' '-s' '-a' '1.0.0' '-m' 'first release'" &&
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

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $tags->sign(Name::of('1.0.0'), Message::of('first release'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testAll()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
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
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn("1.0.0|||first release|||Sat, 16 Mar 2019 12:09:24 +0100\n1.0.1|||fix eris dependency|||Sat, 30 Mar 2019 12:30:35 +0100\n2.0.0|||tag in first 9 days of month is parsed|||Wed, 1 Jun 2022 12:00:00 +0200");

        $all = $tags->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(3, $all);
        $all = $all->toList();
        $this->assertSame('1.0.0', \current($all)->name()->toString());
        $this->assertSame('first release', \current($all)->message()->toString());
        $this->assertSame(
            '2019-03-16T11:09:24+00:00',
            \current($all)->date()->format(new ISO8601),
        );
        \next($all);
        $this->assertSame('1.0.1', \current($all)->name()->toString());
        $this->assertSame('fix eris dependency', \current($all)->message()->toString());
        $this->assertSame(
            '2019-03-30T11:30:35+00:00',
            \current($all)->date()->format(new ISO8601),
        );
        \next($all);
        $this->assertSame('2.0.0', \current($all)->name()->toString());
        $this->assertSame('tag in first 9 days of month is parsed', \current($all)->message()->toString());
        $this->assertSame(
            '2022-06-01T10:00:00+00:00',
            \current($all)->date()->format(new ISO8601),
        );
    }

    public function testAllWhenNoTag()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                Path::of('/tmp/foo'),
            ),
            new Clock(new UTC),
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
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
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn(' ');

        $all = $tags->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(0, $all);
    }
}
