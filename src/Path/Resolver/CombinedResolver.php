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
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return File[]
     */
    public function find(Path $path, Path $parentPath = null): array
    {
        return array_merge(
            $this->filesystemResolver->find($path, $parentPath),
            $this->pageResolver->find($path, $parentPath)
        );
    }

    /**
     * Resolve the given file name and path.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return null|File
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
