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
use Innmind\TimeContinuum\{
    TimeContinuum\Earth,
    Format\ISO8601,
    Timezone\Earth\UTC
};
use Innmind\Immutable\SetInterface;
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
                return (string) $command === "git 'push' '--tags'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
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
                return (string) $command === "git 'tag' '1.0.0' '-a' '-m' 'first release'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
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
                return (string) $command === "git 'tag' '1.0.0'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
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
                return (string) $command === "git 'tag' '-s' '-a' '1.0.0' '-m' 'first release'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $tags = new Tags(
            new Binary(
                $server,
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
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
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("1.0.0|||first release|||Sat, 16 Mar 2019 12:09:24 +0100\n1.0.1|||fix eris dependency|||Sat, 30 Mar 2019 12:30:35 +0100");

        $all = $tags->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Tag::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('1.0.0', (string) $all->current()->name());
        $this->assertSame('first release', (string) $all->current()->message());
        $this->assertSame(
            '2019-03-16T11:09:24+00:00',
            $all->current()->date()->format(new ISO8601)
        );
        $all->next();
        $this->assertSame('1.0.1', (string) $all->current()->name());
        $this->assertSame('fix eris dependency', (string) $all->current()->message());
        $this->assertSame(
            '2019-03-30T11:30:35+00:00',
            $all->current()->date()->format(new ISO8601)
        );
    }

    public function testAllWhenNoTag()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                new Path('/tmp/foo')
            ),
            new Earth(new UTC)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'" &&
                    $command->workingDirectory() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn(' ');

        $all = $tags->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Tag::class, (string) $all->type());
        $this->assertCount(0, $all);
    }
}
