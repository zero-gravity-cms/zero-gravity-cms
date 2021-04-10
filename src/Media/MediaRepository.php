<?php

namespace ZeroGravity\Cms\Media;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\SinglePathResolver;

final class MediaRepository
{
    private SinglePathResolver $pathResolver;

    public function __construct(SinglePathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * @param string|Path $relativePath
     */
    public function getFile($relativePath): ?File
    {
        if (!$relativePath instanceof Path) {
            $relativePath = new Path((string) $relativePath);
        }

        $file = $this->pathResolver->get($relativePath);
        if (null !== $file) {
            return $file;
        }

        return null;
    }
}
