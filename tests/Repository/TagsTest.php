<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tags,
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
                return (string) $command === 'git push --tags' &&
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
                return (string) $command === 'git tag -a 1.0.0 -m \'first release\'' &&
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

        $this->assertSame($tags, $tags->add('1.0.0', 'first release'));
    }

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        (new Tags(new Binary($this->createMock(Server::class), new Path('/'))))->add('', 'foo');
    }

    /**
     * @expectedException Innmind\Git\Exception\DomainException
     */
    public function testThrowWhenEmptyMessage()
    {
        (new Tags(new Binary($this->createMock(Server::class), new Path('/'))))->add('foo', '');
    }
}
