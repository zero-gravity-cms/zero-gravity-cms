<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

class PageResolver extends AbstractResolver
{
    private ContentRepository $contentRepository;

    public function __construct(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ? File
    {
        $pagePath = $path->getDirectory();
        $filePath = $path->getFile();
        if (null === $filePath) {
            return null;
        }
        $parentPath = $parentPath ? clone $parentPath : new Path('');

        $fullPath = $parentPath->appendPath($pagePath);
        $fullPath->normalize();
        $searchPath = $fullPath->getDirectory();

        $page = $this->contentRepository->getPage('/'.trim($searchPath->toString(), '/'));
        $subPath = '';
        while (null === $page && count($searchPath->getElements()) > 1) {
            $subPath = $searchPath->getLastElement().'/'.$subPath;
            $searchPath->dropLastElement();
            $page = $this->contentRepository->getPage('/'.trim($searchPath->toString(), '/'));
        }

        if (null === $page) {
            return null;
        }

        $file = $page->getFile($subPath.$filePath->toString());

        return $file;
    }
}
