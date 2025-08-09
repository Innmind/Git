<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository;

use Innmind\Git\{
    Repository\Tags,
    Binary,
    Message,
    Repository\Tag\Name,
};
use Innmind\Server\Control\Servers\Mock;
use Innmind\Url\Path;
use Innmind\TimeContinuum\{
    Clock,
    Format,
};
use Innmind\Immutable\{
    Set,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class TagsTest extends TestCase
{
    public function testPush()
    {
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'push' '--tags'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
            });

        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'tag' '1.0.0' '-a' '-m' 'first release'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
            });

        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'tag' '1.0.0'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
            });

        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
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
        $server = Mock::new($this->assert())
            ->willExecute(function($command) {
                $this->assertSame(
                    "git 'tag' '-s' '-a' '1.0.0' '-m' 'first release'",
                    $command->toString(),
                );
                $this->assertSame(
                    '/tmp/foo',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
            });

        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
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
        $server = Mock::new($this->assert())
            ->willExecute(
                function($command) {
                    $this->assertSame(
                        "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'",
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
                static fn($_, $builder) => $builder->success([[
                    "1.0.0|||first release|||Sat, 16 Mar 2019 12:09:24 +0100\n1.0.1|||fix eris dependency|||Sat, 30 Mar 2019 12:30:35 +0100\n2.0.0|||tag in first 9 days of month is parsed|||Wed, 1 Jun 2022 12:00:00 +0200",
                    'output',
                ]]),
            );
        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
        );

        $all = $tags->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(3, $all);
        $all = $all->toList();
        $this->assertSame('1.0.0', \current($all)->name()->toString());
        $this->assertSame('first release', \current($all)->message()->toString());
        $this->assertSame(
            '2019-03-16T11:09:24+00:00',
            \current($all)->date()->format(Format::iso8601()),
        );
        \next($all);
        $this->assertSame('1.0.1', \current($all)->name()->toString());
        $this->assertSame('fix eris dependency', \current($all)->message()->toString());
        $this->assertSame(
            '2019-03-30T11:30:35+00:00',
            \current($all)->date()->format(Format::iso8601()),
        );
        \next($all);
        $this->assertSame('2.0.0', \current($all)->name()->toString());
        $this->assertSame('tag in first 9 days of month is parsed', \current($all)->message()->toString());
        $this->assertSame(
            '2022-06-01T10:00:00+00:00',
            \current($all)->date()->format(Format::iso8601()),
        );
    }

    public function testAllWhenNoTag()
    {
        $server = Mock::new($this->assert())
            ->willExecute(
                function($command) {
                    $this->assertSame(
                        "git 'tag' '--list' '--format=%(refname:strip=2)|||%(subject)|||%(creatordate:rfc2822)'",
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
                static fn($_, $builder) => $builder->success([[' ', 'output']]),
            );
        $tags = Tags::of(
            Binary::of(
                $server,
                Path::of('/tmp/foo'),
            ),
            Clock::live()->switch(static fn($timezones) => $timezones->utc()),
        );

        $all = $tags->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(0, $all);
    }
}
