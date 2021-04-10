<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

interface SinglePathResolver
{
    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ?File;
}
