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
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
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
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'push' '--tags'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );

        $this->assertSame($tags, $tags->push());
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
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'tag' '1.0.0' '-a' '-m' 'first release'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );

        $this->assertSame(
            $tags,
            $tags->add(new Name('1.0.0'), new Message('first release'))
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
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'tag' '1.0.0'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );

        $this->assertSame(
            $tags,
            $tags->add(new Name('1.0.0'))
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
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'tag' '-s' '-a' '1.0.0' '-m' 'first release'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );

        $this->assertSame(
            $tags,
            $tags->sign(new Name('1.0.0'), new Message('first release'))
        );
    }

    public function testAll()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn("1.0.0|||first release|||Sat, 16 Mar 2019 12:09:24 +0100\n1.0.1|||fix eris dependency|||Sat, 30 Mar 2019 12:30:35 +0100");

        $all = $tags->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Tag::class, (string) $all->type());
        $this->assertCount(2, $all);
        $all = unwrap($all);
        $this->assertSame('1.0.0', (string) \current($all)->name());
        $this->assertSame('first release', (string) \current($all)->message());
        $this->assertSame(
            '2019-03-16T11:09:24+00:00',
            \current($all)->date()->format(new ISO8601)
        );
        \next($all);
        $this->assertSame('1.0.1', (string) \current($all)->name());
        $this->assertSame('fix eris dependency', (string) \current($all)->message());
        $this->assertSame(
            '2019-03-30T11:30:35+00:00',
            \current($all)->date()->format(new ISO8601)
        );
    }

    public function testAllWhenNoTag()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                Path::of('/tmp/foo')
            ),
            new Clock(new UTC)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return $command->toString() === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
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
        $this->assertSame(Tag::class, (string) $all->type());
        $this->assertCount(0, $all);
    }
}
