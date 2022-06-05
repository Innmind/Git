<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\Repository\Remote\Url;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class UrlTest extends TestCase
{
    use BlackBox;

    public function testReturnNothingWhenGivenAnyRandomString()
    {
        $this
            ->forAll(Set\Elements::of("\x01", "\x02", "\x03"))
            ->then(function(string $string): void {
                $this->assertNull(Url::maybe($string)->match(
                    static fn($url) => $url,
                    static fn() => null,
                ));
            });
    }

    /**
     * @dataProvider formats
     */
    public function testInterface(string $format)
    {
        $this->assertSame($format, Url::of($format)->toString());
    }

    public function formats(): array
    {
        return [
            ['/tmp'],
            ['/tmp/sub/dir'],
            ['/tmp/sub/dir/project.git'],
            ['file:///tmp/sub/dir'],
            ['file:///tmp/sub/dir/project.git'],
            ['https://example.com/gitproject.git'],
            ['ssh://user@server/project.git'],
            ['user@server:project.git'],
        ];
    }
}
