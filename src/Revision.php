<?php
declare(strict_types = 1);

namespace Innmind\Git;

interface Revision
{
    public function __toString(): string;
}
