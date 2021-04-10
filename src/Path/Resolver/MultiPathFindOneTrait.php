<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Exception\ResolverException;
use ZeroGravity\Cms\Path\Path;

trait MultiPathFindOneTrait
{
    abstract public function find(Path $path, Path $parentPath = null): array;

    abstract public function get(Path $path, Path $parentPath = null): ?File;

    /**
     * Find a single matching file.
     *
     * @param bool $strict Accept only 1 found file, throw ResolverException if more than 1
     */
    public function findOne(Path $path, Path $parentPath = null, bool $strict = true): ?File
    {
        $file = $this->get(clone $path, (null !== $parentPath) ? clone $parentPath : null);
        if (null !== $file) {
            return $file;
        }

        $files = $this->find(clone $path, (null !== $parentPath) ? clone $parentPath : null);
        if (0 === count($files)) {
            return null;
        }
        if (1 === count($files)) {
            return array_shift($files);
        }
        if (count($files) > 1 && !$strict) {
            return array_shift($files);
        }

        throw ResolverException::moreThanOneFileMatchingPattern($path->toString(), $files);
    }
}
