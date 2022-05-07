<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Remotes,
    Repository\Remote,
    Repository\Remote\Name,
    Repository\Remote\Url,
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
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class RemotesTest extends TestCase
{
    public function testAll()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn(<<<REMOTES
origin
gitlab
local
REMOTES
            );

        $remotes = new Remotes(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $all = $remotes->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Remote::class, (string) $all->type());
        $this->assertCount(3, $all);
        $all = unwrap($all);
        $this->assertSame('origin', \current($all)->name()->toString());
        \next($all);
        $this->assertSame('gitlab', \current($all)->name()->toString());
        \next($all);
        $this->assertSame('local', \current($all)->name()->toString());
    }

    public function testGet()
    {
        $remotes = new Remotes(
            new Binary(
                $this->createMock(Server::class),
                Path::of('watev'),
            ),
        );

        $remote = $remotes->get($expected = new Name('origin'));

        $this->assertInstanceOf(Remote::class, $remote);
        $this->assertSame($expected, $remote->name());
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
                return $command->toString() === "git 'remote' 'add' 'origin' 'git@github.com:Innmind/Git.git'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $remotes = new Remotes(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $remote = $remotes->add(
            $expected = new Name('origin'),
            new Url('git@github.com:Innmind/Git.git'),
        );

        $this->assertInstanceOf(Remote::class, $remote);
        $this->assertSame($expected, $remote->name());
    }

    public function testRemove()
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
                return $command->toString() === "git 'remote' 'remove' 'origin'" &&
                    $command->workingDirectory()->toString() === '/tmp/foo';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $remotes = new Remotes(
            new Binary(
                $server,
                Path::of('/tmp/foo'),
            ),
        );

        $this->assertNull($remotes->remove(new Name('origin')));
    }
}
