<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\PathElement;

final class FilesystemResolver extends AbstractResolver implements MultiPathResolver
{
    use MultiPathFindOneTrait;

    public function __construct(
        private readonly FileFactory $fileFactory
    ) {
    }

    /**
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @return File[]
     */
    public function find(Path $path, Path $parentPath = null): array
    {
        /* @var $parentPath Path */
        $this->preparePaths($path, $parentPath);

        $finder = Finder::create()
            ->notName('*.meta.yaml')
            ->sortByName()
            ->files()
        ;

        if (!$path->isRegex() && ($path->isSingleElement() || $path->isGlob())) {
            $finder->name($path->toString());
        } else {
            $finder->path($path->toString());
        }
        $finder->in($this->buildBaseDir($parentPath));

        /* @noinspection NullPointerExceptionInspection */
        return $this->doFind($finder, $parentPath);
    }

    private function preparePaths(Path &$path, Path &$parentPath = null): void
    {
        if (!$parentPath instanceof Path) {
            $parentPath = new Path('');
        }
        $path->normalize($parentPath);

        if ($path->isAbsolute() && !$path->isRegex()) {
            $path = $this->toRegexMatchStart($path);
        }

        if ($path->isAbsolute()) {
            return;
        }
        if (!$path->isGlob()) {
            return;
        }

        $this->moveNonGlobsToParent($path, $parentPath);
    }

    /**
     * @return File[]
     */
    private function doFind(Finder $finder, Path $parentPath): array
    {
        $found = [];
        foreach ($finder as $file) {
            /* @var $file SplFileInfo */
            if ($parentPath->hasElements()) {
                // rewrite files to get relative paths to the real basePath
                $relativePath = clone $parentPath;
                $relativePath->appendElement(new PathElement($file->getRelativePath()));

                $relativePathName = clone $parentPath;
                $relativePathName->appendElement(new PathElement($file->getRelativePathname()));

                $file = new SplFileInfo(
                    $file->getPathname(),
                    trim($relativePath->toString(), '/'),
                    trim($relativePathName->toString(), '/')
                );
            }

            $pathname = $file->getRelativePathname();
            $found[$pathname] = $this->fileFactory->createFile($pathname);
        }

        return $found;
    }

    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ?File
    {
        if (!$parentPath instanceof Path) {
            $parentPath = new Path('');
        }
        $fullPath = $parentPath->appendPath($path);
        $fullPath->normalize();

        $trimmedPath = ltrim($fullPath->toString(), '/');
        if (str_ends_with($trimmedPath, '.meta.yaml')) {
            return null;
        }

        $testPath = $this->buildBaseDir().'/'.$trimmedPath;
        if (!is_file($testPath)) {
            return null;
        }

        return $this->fileFactory->createFile('/'.$trimmedPath);
    }

    private function buildBaseDir(Path $parentPath = null): string
    {
        $basePath = $this->fileFactory->getBasePath();
        if (!$parentPath instanceof Path) {
            return $basePath;
        }

        $parentPath->normalize();
        if ($parentPath->hasElements()) {
            $basePath .= '/'.ltrim($parentPath->toString(), '/');
        }

        return $basePath;
    }
}
