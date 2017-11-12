<?php
declare(strict_types = 1);

namespace Tests\Innmind\Git\Repository\Remote;

use Innmind\Git\{
    Repository\Remote\Url,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class UrlTest extends TestCase
{
    use TestTrait;

    public function testThrowWhenGivenAnyRandomString()
    {
        $this
            ->forAll(Generator\string())
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
        $this->assertSame($format, (string) new Url($format));
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
