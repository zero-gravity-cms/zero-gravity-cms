<?php

namespace ZeroGravity\Cms\Content;

use Symfony\Component\Filesystem\Filesystem;
use ZeroGravity\Cms\Content\Meta\MetadataLoader;
use ZeroGravity\Cms\Exception\FilesystemException;

class FileFactory
{
    private FileTypeDetector $fileTypeDetector;

    private MetadataLoader $metadataLoader;

    private string $basePath;

    /**
     * FileFactory constructor.
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

    public function createFile(string $pathname): File
    {
        return new File(
            $pathname,
            $this->basePath,
            $this->metadataLoader->loadMetadataForFile($pathname, $this->basePath),
            $this->fileTypeDetector->getType($pathname)
        );
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
