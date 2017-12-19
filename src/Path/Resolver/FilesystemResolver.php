<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\PathElement;

class FilesystemResolver extends AbstractResolver implements MultiPathResolver
{
    use MultiPathFindOneTrait;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param FileFactory $fileFactory
     */
    public function __construct(FileFactory $fileFactory)
    {
        $this->filesystem = new Filesystem();
        $this->basePath = $fileFactory->getBasePath();
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
        if (null === $parentPath) {
            $parentPath = new Path('');
        }
        $path->normalize($parentPath);

        $finder = Finder::create()
            ->notName('*.meta.yaml')
            ->sortByName()
            ->files()
        ;

        if ($path->isAbsolute() && !$path->isRegex()) {
            $path = $this->toRegexMatchStart($path);
        }
        if (!$path->isAbsolute() && $path->isGlob()) {
            // try moving pattern parts into inPath because globs don't work with paths
            $this->moveNonGlobsToParent($path, $parentPath);
        }

        if (($path->isSingleElement() || $path->isGlob()) && !$path->isRegex()) {
            $finder->name($path->toString());
        } else {
            $finder->path($path->toString());
        }

        $finder->in($this->buildBaseDir($parentPath));
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

            $found[$file->getRelativePathname()] = $this->fileFactory->createFile($file->getRelativePathname());
        }

        return $found;
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
        if (null === $parentPath) {
            $parentPath = new Path('');
        }
        $fullPath = $parentPath->appendPath($path);
        $fullPath->normalize();

        $trimmedPath = ltrim($fullPath->toString(), '/');
        if ('.meta.yaml' === substr($trimmedPath, -10)) {
            return null;
        }
        $testPath = $this->buildBaseDir().'/'.$trimmedPath;
        if ($this->filesystem->exists($testPath) && is_file($testPath)) {
            return $this->fileFactory->createFile('/'.$trimmedPath);
        }

        return null;
    }

    /**
     * @param Path $parentPath
     *
     * @return string
     */
    protected function buildBaseDir(Path $parentPath = null): string
    {
        if (null === $parentPath) {
            return $this->basePath;
        }

        $parentPath->normalize();
        $basePath = $this->basePath;
        if ($parentPath->hasElements()) {
            $basePath .= '/'.ltrim($parentPath->toString(), '/');
        }

        return $basePath;
    }
}
