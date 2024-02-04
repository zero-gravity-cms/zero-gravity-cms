<?php

namespace ZeroGravity\Cms\Media;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\SinglePathResolver;

final readonly class MediaRepository
{
    public function __construct(
        private SinglePathResolver $pathResolver
    ) {
    }

    public function getFile(Path|string $relativePath): ?File
    {
        if (!$relativePath instanceof Path) {
            $relativePath = new Path($relativePath);
        }

        $file = $this->pathResolver->get($relativePath);

        return $file ?? null;
    }
}
