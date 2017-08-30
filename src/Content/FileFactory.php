<?php

namespace ZeroGravity\Cms\Content;

use Symfony\Component\Filesystem\Filesystem;
use ZeroGravity\Cms\Exception\FilesystemException;

class FileFactory
{
    /**
     * @var FileTypeDetector
     */
    private $fileTypeDetector;

    /**
     * @var MetadataLoader
     */
    private $metadataLoader;

    /**
     * @var string
     */
    private $basePath;

    /**
     * FileFactory constructor.
     *
     * @param FileTypeDetector $fileTypeDetector
     * @param MetadataLoader   $metadataLoader
     * @param string           $basePath
     */
    public function __construct(FileTypeDetector $fileTypeDetector, MetadataLoader $metadataLoader, string $basePath)
    {
        $fs = new Filesystem();
        if (!$fs->exists($basePath) || !is_dir($basePath)) {
            throw FilesystemException::contentDirectoryDoesNotExist($basePath);
        }

        $this->fileTypeDetector = $fileTypeDetector;
        $this->metadataLoader = $metadataLoader;
        $this->basePath = $basePath;
    }

    /**
     * @param string $pathname
     *
     * @return File
     */
    public function createFile(string $pathname): File
    {
        return new File(
            $pathname,
            $this->basePath,
            $this->metadataLoader->loadMetadataForFile($pathname, $this->basePath),
            $this->fileTypeDetector->getType($pathname)
        );
    }

    /**
     * Get the full filesystem path to the given file.
     *
     * @param File $file
     *
     * @return string
     */
    public function getFilesystemPathname(File $file): string
    {
        return sprintf('%s/%s', rtrim($this->basePath, '/'), $file->getPathname());
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
