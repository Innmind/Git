<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\Repository\Remote\Url;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\DataProvider;

class UrlTest extends TestCase
{
    use BlackBox;

    public function testReturnNothingWhenGivenAnyRandomString(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of("\x01", "\x02", "\x03"))
            ->prove(function(string $string): void {
                $this->assertNull(Url::maybe($string)->match(
                    static fn($url) => $url,
                    static fn() => null,
                ));
            });
    }

    #[DataProvider('formats')]
    public function testInterface(string $format)
    {
        $this->assertSame($format, Url::of($format)->toString());
    }

    public static function formats(): array
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
