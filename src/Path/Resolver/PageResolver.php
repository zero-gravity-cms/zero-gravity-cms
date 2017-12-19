<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Path\Path;

class PageResolver extends AbstractResolver
{
    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param ContentRepository $contentRepository
     * @param string            $basePath
     * @param FileFactory       $fileFactory
     */
    public function __construct(ContentRepository $contentRepository, string $basePath, FileFactory $fileFactory)
    {
        $this->contentRepository = $contentRepository;
        $this->basePath = $basePath;
        $this->fileFactory = $fileFactory;
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
