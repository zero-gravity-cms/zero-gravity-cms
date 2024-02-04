<?php

namespace ZeroGravity\Cms\Content;

use Symfony\Component\Filesystem\Filesystem;
use ZeroGravity\Cms\Content\Meta\MetadataLoader;
use ZeroGravity\Cms\Exception\FilesystemException;

final readonly class FileFactory
{
    public function __construct(
        private FileTypeDetector $fileTypeDetector,
        private MetadataLoader $metadataLoader,
        private string $basePath,
    ) {
        $fs = new Filesystem();
        if (!$fs->exists($this->basePath) || !is_dir($this->basePath)) {
            throw FilesystemException::contentDirectoryDoesNotExist($this->basePath);
        }
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
