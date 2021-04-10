<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

interface MultiPathResolver extends SinglePathResolver
{
    /**
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @return File[]
     */
    public function find(Path $path, Path $parentPath = null): array;

    /**
     * Find a single matching file.
     *
     * @param bool $strict Accept only 1 found file, throw ResolverException if more than 1
     */
    public function findOne(Path $path, Path $parentPath = null, bool $strict = true): ?File;
}
