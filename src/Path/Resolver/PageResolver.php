<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Finder\Glob;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\Page;
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
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return File[]
     */
    public function find(Path $path, Path $parentPath = null): array
    {
        return [];

        // TODO: this is outdated code that does not find any files.
        // $pages = $this->filterPagesByPath($path, $parentPath);
        //
        // $found = [];
        // foreach ($pages as $page) {
        //     $found[ltrim($page->getFilesystemPath()->toString(), '/')] = $this->pageToFile($page);
        // }
        //
        // return $found;
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

    /**
     * @param Path      $path
     * @param Path|null $parentPath
     *
     * @return Page[]
     */
    protected function filterPagesByPath(Path $path, Path $parentPath = null)
    {
        $allPages = $this->contentRepository->getAllPages();

        $foundPages = array_filter($allPages, function (Page $page) use ($path, $parentPath) {
            return $this->pageMatchesPath($page, $path, $parentPath);
        });

        return $foundPages;
    }

    /**
     * @param Page $page
     * @param Path $path
     * @param Path $parentPath
     *
     * @return bool
     */
    protected function pageMatchesPath(Page $page, Path $path, Path $parentPath = null) : bool
    {
        $pagePathString = $page->getPath()->toString();
        if (null !== $parentPath) {
            $testPath = $parentPath->appendPath($path);
        } else {
            $testPath = clone $path;
        }

        if ($pagePathString === '/'.ltrim($testPath->toString(), '/')) {
            return true;
        }

        if (!$testPath->isAbsolute() && false !== strpos($pagePathString, $testPath->toString())) {
            return true;
        }

        if ($testPath->isRegex() && preg_match($testPath->toString(), $pagePathString)) {
            return true;
        }

        if ($testPath->isGlob()) {
            $regex = Glob::toRegex($testPath->toString());
            if (!$path->isAbsolute()) {
                $regex = substr_replace($regex, '/([^/]+/)*', 2, 0);
            } else {
                $regex = substr_replace($regex, '/', 2, 0);
            }

            if (preg_match($regex, $pagePathString)) {
                return true;
            }
        }

        return false;
    }
}
