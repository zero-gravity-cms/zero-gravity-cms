<?php

namespace ZeroGravity\Cms\Exception;

use InvalidArgumentException;

class UnsafePathException extends InvalidArgumentException implements ZeroGravityException
{
    /**
     * @param $path
     *
     * @return static
     */
    public static function pathNotAllowed($path)
    {
        return new static(sprintf('Path %s is unsafe, because it leaves the parent structure', $path));
    }
}
