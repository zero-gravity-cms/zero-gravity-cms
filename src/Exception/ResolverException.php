<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use ZeroGravity\Cms\Content\File;

class ResolverException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param File[] $files
     */
    public static function moreThanOneFileMatchingPattern(string $pattern, array $files): self
    {
        $files = array_map(fn (File $file) => $file->getPathname(), $files);

        return new static(
            sprintf(
                'There is more than 1 file matching the pattern "%s". This is not allowed if $strict=true. Files: %s',
                $pattern,
                implode(', ', $files)
            )
        );
    }
}
