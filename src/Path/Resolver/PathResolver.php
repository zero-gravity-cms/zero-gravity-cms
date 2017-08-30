<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

interface PathResolver
{
    /**
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return File[]
     */
    public function find(Path $path, Path $parentPath = null): array;

    /**
     * Resolve the given file name and path.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return null|File
     */
    public function get(Path $path, Path $parentPath = null): ? File;

    /**
     * Find a single matching file.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     * @param bool      $strict     Accept only 1 found file, throw ResolverException if more than 1
     *
     * @return null|File
     */
    public function findOne(Path $path, Path $parentPath = null, bool $strict = true) : ? File;
}
