<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Path;

final class StringPatternUtil
{
    /**
     * Checks whether the string is a regex.
     *
     * @return bool True if the element contains a valid regular expression
     *
     * @see https://stackoverflow.com/a/12941133/22592
     */
    public static function stringContainsRegex(string $pathString): bool
    {
        return !(false === @preg_match($pathString, ''));
    }

    /**
     * Simple check if the given string contains glob characters.
     */
    public static function stringContainsGlob(string $pathString): bool
    {
        if (self::stringContainsRegex($pathString)) {
            return false;
        }

        return
            false !== strpos($pathString, '*')
            || false !== strpos($pathString, '?')
            || preg_match('/\{.*\}/', $pathString);
    }
}
