<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git;

use Innmind\Git\Binary;
use Innmind\Server\Control\{
    Server\Command,
    Servers\Mock,
};
use Innmind\Url\Path;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
    public function testSuccessfulInvokation()
    {
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'watev'",
                    $command->toString(),
                );
                $this->assertFalse($command->toBeRunInBackground());
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
            });

        $bin = new Binary(
            $server,
            Path::of('/tmp/foo'),
        );

        $this->assertInstanceOf(Command::class, $bin->command());
        $this->assertSame('git', $bin->command()->toString());
        $this->assertSame('/tmp/foo', $bin->command()->workingDirectory()->match(
            static fn($path) => $path->toString(),
            static fn() => null,
        ));
        $this->assertSame(0, $bin($bin->command()->withArgument('watev'))->match(
            static fn($output) => $output->size(),
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenCommandFailed()
    {
        $server = Mock::new($this->assert())
            ->willExecute(
                function($command) {
                    $this->assertSame(
                        "git 'watev'",
                        $command->toString(),
                    );
                    $this->assertSame(
                        '/tmp/foo',
                        $command->workingDirectory()->match(
                            static fn($path) => $path->toString(),
                            static fn() => null,
                        ),
                    );
                },
                static fn($_, $builder) => $builder->failed(),
            );

        $bin = new Binary(
            $server,
            Path::of('/tmp/foo'),
        );

        $this->assertNull($bin($bin->command()->withArgument('watev'))->match(
            static fn($output) => $output,
            static fn() => null,
        ));
    }

    public function testHomeIsAddedToTheCommandEnvironment()
    {
        $bin = new Binary(
            Mock::new($this->assert()),
            Path::of('/tmp/foo'),
            Path::of('/Users/baptouuuu'),
        );

        $this->assertSame('/Users/baptouuuu', $bin->command()->environment()->get('HOME')->match(
            static fn($home) => $home,
            static fn() => null,
        ));
    }
}
