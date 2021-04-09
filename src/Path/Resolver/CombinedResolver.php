<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

class CombinedResolver extends AbstractResolver
{
    /**
     * @var FilesystemResolver
     */
    private $filesystemResolver;

    /**
     * @var PageResolver
     */
    private $pageResolver;

    public function __construct(FilesystemResolver $filesystemResolver, PageResolver $pageResolver)
    {
        $this->filesystemResolver = $filesystemResolver;
        $this->pageResolver = $pageResolver;
    }

    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ? File
    {
        $found = $this->filesystemResolver->get($path, $parentPath);
        if (null !== $found) {
            return $found;
        }

        return $this->pageResolver->get($path, $parentPath);
    }
}
