<?php

namespace ZeroGravity\Cms\Path;

use ZeroGravity\Cms\Exception\UnsafePathException;

/**
 * Thanks to https://stackoverflow.com/a/39796579/22592.
 */
class PathNormalizer
{
    /**
     * Normalize a path, resolving all relative jumps ("../").
     * If the path would leave the base level, an exception is thrown.
     *
     * $inPath can be used to step outside the filename level and is changed back by reference.
     *
     * @param string $filename
     * @param string $inPath   Passed by reference!
     *
     * @return string
     */
    public static function normalize(string $filename, string &$inPath = ''): string
    {
        $path = [];

        if (!empty($inPath)) {
            $normalizedInPath = static::normalize($inPath);
            $inPathParts = static::pathToArray($normalizedInPath);
        } else {
            $inPathParts = [];
        }

        foreach (explode('/', $filename) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') {
                continue;
            }

            if ($part !== '..') {
                // cool, we found a new part
                array_push($path, $part);
            } elseif (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            } elseif (count($inPathParts) > 0) {
                // $inPath allows some stepping out of the safe path
                array_pop($inPathParts);
            } else {
                // now, here we don't like
                throw UnsafePathException::pathNotAllowed($filename);
            }
        }

        $inPath = implode('/', $inPathParts);

        return implode('/', $path);
    }

    /**
     * Split a path into array of parts containing everything but empty nonsense as found in "/./" or "//".
     *
     * @param string $path
     *
     * @return string[]
     */
    public static function pathToArray(string $path)
    {
        return array_filter(explode('/', $path), function ($part) {
            return !empty($part) && $part !== '.';
        });
    }

    /**
     * Normalize a path, resolving all relative jumps ("../").
     * If the path would leave the base level, an exception is thrown.
     *
     * $parentPath can be used to step outside the filename level.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     */
    public static function normalizePath(Path $path, Path $parentPath = null)
    {
        $normalizedElements = [];
        $parentPathElements = (null !== $parentPath) ? $parentPath->getElements() : [];

        foreach ($path->getElements() as $element) {
            if (!$element->isParentReference()) {
                // cool, we found a new part
                array_push($normalizedElements, $element);
            } elseif (count($normalizedElements) > 0) {
                // going back up? sure
                array_pop($normalizedElements);
            } elseif (count($parentPathElements) > 0) {
                // parent path allows some stepping out of the safe zone
                array_pop($parentPathElements);
            } else {
                // now, here we don't like
                throw UnsafePathException::pathNotAllowed($path->toString());
            }
        }

        if (null !== $parentPath) {
            $parentPath->setElements($parentPathElements);
        }
        $path->setElements($normalizedElements);
    }
}
