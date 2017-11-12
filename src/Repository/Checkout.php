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
    private $execute;

    public function __construct(Binary $binary)
    {
        $this->execute = $binary;
    }

    public function file(PathInterface $path): self
    {
        ($this->execute)("checkout -- $path");

        return $this;
    }

    public function revision(Revision $revision): self
    {
        ($this->execute)("checkout $revision");

        return $this;
    }
}
