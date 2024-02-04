<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Path\Path;

final class PageResolver extends AbstractResolver
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
    }

    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ?File
    {
        $pagePath = $path->getDirectory();
        $filePath = $path->getFile();
        if (!$filePath instanceof Path) {
            return null;
        }
        $parentPath = $parentPath instanceof Path ? clone $parentPath : new Path('');

        $fullPath = $parentPath->appendPath($pagePath);
        $fullPath->normalize();

        $searchPath = $fullPath->getDirectory();

        $page = $this->contentRepository->getPage('/'.trim($searchPath->toString(), '/'));
        $subPath = '';
        while (!$page instanceof ReadablePage && count($searchPath->getElements()) > 1) {
            $subPath = $searchPath->getLastElement().'/'.$subPath;
            $searchPath->dropLastElement();
            $page = $this->contentRepository->getPage('/'.trim($searchPath->toString(), '/'));
        }

        if (!$page instanceof ReadablePage) {
            return null;
        }

        return $page->getFile($subPath.$filePath->toString());
    }
}
