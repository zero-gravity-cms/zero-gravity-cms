<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

interface SinglePathResolver
{
    /**
     * Resolve the given file name and path.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return null|File
     */
    public function get(Path $path, Path $parentPath = null): ? File;
}
