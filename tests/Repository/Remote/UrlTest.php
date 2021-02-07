<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\{
    Repository\Remote\Url,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class UrlTest extends TestCase
{
    use BlackBox;

    public function testThrowWhenGivenAnyRandomString()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function(string $string): void {
                $this->expectException(DomainException::class);

                new Url($string);
            });
    }

    /**
     * @dataProvider formats
     */
    public function testInterface(string $format)
    {
        $this->assertSame($format, (new Url($format))->toString());
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
