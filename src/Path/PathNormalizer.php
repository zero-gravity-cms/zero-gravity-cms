<?php

namespace ZeroGravity\Cms\Path;

use ZeroGravity\Cms\Exception\UnsafePathException;

/**
 * Thanks to https://stackoverflow.com/a/39796579/22592.
 */
final class PathNormalizer
{
    /**
     * Normalize a path, resolving all relative jumps ("../").
     * If the path would leave the base level, an exception is thrown.
     *
     * $parentPath can be used to step outside the filename level.
     */
    public static function normalizePath(Path $path, Path $parentPath = null): void
    {
        $normalizedElements = [];
        $parentPathElements = (null !== $parentPath) ? $parentPath->getElements() : [];

        foreach ($path->getElements() as $element) {
            if (!$element->isParentReference()) {
                // cool, we found a new part
                $normalizedElements[] = $element;
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
