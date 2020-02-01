<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision,
};
use Innmind\Url\Path;

final class Checkout
{
    private Binary $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    public function file(Path $path): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument('--')
                ->withArgument($path->toString()),
        );
    }

    public function revision(Revision $revision): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument($revision->toString()),
        );
    }
}
