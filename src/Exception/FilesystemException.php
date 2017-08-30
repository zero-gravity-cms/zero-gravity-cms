<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

class FilesystemException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param string $path
     *
     * @return static
     */
    public static function contentDirectoryDoesNotExist(string $path)
    {
        return new static(sprintf('The content directory "%s" does not exist', $path));
    }
}
