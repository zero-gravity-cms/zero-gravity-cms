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
        $files = array_map(static fn (File $file): string => $file->getPathname(), $files);

        return new self(
            sprintf(
                'There is more than 1 file matching the pattern "%s". This is not allowed if $strict=true. Files: %s',
                $pattern,
                implode(', ', $files)
            )
        );
    }
}
