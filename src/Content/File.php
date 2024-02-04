<?php

namespace ZeroGravity\Cms\Content;

use Stringable;
use ZeroGravity\Cms\Content\Meta\Metadata;

/**
 * This class represents a file relative to a content base dir.
 */
final readonly class File implements Stringable
{
    private string $pathName;
    private string $baseDir;

    public function __construct(
        string $pathName,
        string $baseDir,
        private Metadata $metadata,
        private string $type,
    ) {
        $this->pathName = '/'.ltrim($pathName, '/');
        $this->baseDir = rtrim($baseDir, '/');
    }

    /**
     * Get assigned metadata.
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Get the file type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the filename.
     */
    public function getFilename(): string
    {
        return pathinfo($this->pathName, PATHINFO_BASENAME);
    }

    /**
     * Get the base name of the file, optionally excluding a given suffix.
     */
    public function getBasename(string $suffix = null): string
    {
        return basename($this->pathName, $suffix);
    }

    /**
     * Get basename, applying detected extension.
     */
    public function getDefaultBasename(): string
    {
        $extension = $this->getExtension();
        if ('' !== $extension) {
            return $this->getBasename('.'.$extension);
        }

        return $this->getBasename();
    }

    /**
     * Get the (relative) path to the file.
     */
    public function getPathname(): string
    {
        return $this->pathName;
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return pathinfo($this->pathName, PATHINFO_EXTENSION);
    }

    public function getFilesystemPathname(): string
    {
        return $this->baseDir.$this->pathName;
    }

    public function __toString(): string
    {
        return $this->pathName;
    }
}
