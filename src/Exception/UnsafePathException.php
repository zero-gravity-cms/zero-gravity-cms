<?php

namespace ZeroGravity\Cms\Exception;

use InvalidArgumentException;

class UnsafePathException extends InvalidArgumentException implements ZeroGravityException
{
    public static function pathNotAllowed(string $path): self
    {
        return new static(sprintf('Path %s is unsafe, because it leaves the parent structure', $path));
    }
}
