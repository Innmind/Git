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
            )
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
                return (string) $command === "git 'tag' '-a' '1.0.0' '-m' 'first release'" &&
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
            )
        );

        $this->assertSame(
            $tags,
            $tags->add(new Name('1.0.0'), new Message('first release'))
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
            )
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
            )
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)'" &&
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
            ->willReturn("1.0.0|||first release\n1.0.1|||fix eris dependency");

        $all = $tags->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Tag::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('1.0.0', (string) $all->current()->name());
        $this->assertSame('first release', (string) $all->current()->message());
        $all->next();
        $this->assertSame('1.0.1', (string) $all->current()->name());
        $this->assertSame('fix eris dependency', (string) $all->current()->message());
    }

    public function testAllWhenNoTag()
    {
        $tags = new Tags(
            new Binary(
                $server = $this->createMock(Server::class),
                new Path('/tmp/foo')
            )
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($command): bool {
                return (string) $command === "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)'" &&
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
