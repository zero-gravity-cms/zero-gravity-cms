<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use ZeroGravity\Cms\Content\File;

class ResolverException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param string $pattern
     * @param File[] $files
     *
     * @return static
     */
    public static function moreThanOneFileMatchingPattern(string $pattern, array $files)
    {
        $files = array_map(function (File $file) {
            return $file->getPathname();
        }, $files);

        return new static(
            sprintf(
                'There is more than 1 file matching the pattern "%s". This is not allowed if $strict=true. Files: %s',
                $pattern,
                implode(', ', $files)
            )
        );
    }
}
