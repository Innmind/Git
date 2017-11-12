<?php
declare(strict_types = 1);

namespace Innmind\Git\Repository;

use Innmind\Git\{
    Binary,
    Revision
};
use Innmind\Url\PathInterface;

final class Checkout
{
    private $binary;

    public function __construct(Binary $binary)
    {
        $this->binary = $binary;
    }

    public function file(PathInterface $path): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument('--')
                ->withArgument((string) $path)
        );

        return $this;
    }

    public function revision(Revision $revision): self
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('checkout')
                ->withArgument((string) $revision)
        );

        return $this;
    }
}
