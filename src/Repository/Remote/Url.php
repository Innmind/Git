<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository\Remote;

use Innmind\Git\Exception\DomainException;
use Innmind\Url\{
    Url as BaseUrl,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\Str;

/**
 * Can be any valid url or a string of the format "user@server:repository.git"
 */
final class Url
{
    private string $value;

    public function __construct(string $url)
    {
        try {
            BaseUrl::fromString($url);
        } catch (InvalidArgumentException $e) {
            if (!(new Str($url))->matches('~^\S+@\S+(\.\S+)?:\S+(/\S+)?\.git$~')) {
                throw new DomainException;
            }
        }

        $this->value = $url;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
