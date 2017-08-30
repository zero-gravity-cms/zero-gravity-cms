<?php

namespace ZeroGravity\Cms\Content;

/**
 * This class represents a file relative to a content base dir.
 */
class File
{
    /**
     * @var string
     */
    private $pathName;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $baseDir;

    public function __construct(string $pathName, string $baseDir, Metadata $metadata, string $type)
    {
        $this->pathName = '/'.ltrim($pathName, '/');
        $this->baseDir = rtrim($baseDir, '/');
        $this->metadata = $metadata;
        $this->type = $type;
    }

    /**
     * Get assigned metadata.
     *
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Get the file type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return pathinfo($this->pathName, PATHINFO_BASENAME);
    }

    /**
     * Get the base name of the file, optionally excluding a given suffix.
     *
     * @param string|null $suffix
     *
     * @return string
     */
    public function getBasename(string $suffix = null): string
    {
        return basename($this->pathName, $suffix);
    }

    /**
     * Get basename, applying detected extension.
     *
     * @return string
     */
    public function getDefaultBasename(): string
    {
        $extension = $this->getExtension();
        if (!empty($extension)) {
            return $this->getBasename('.'.$extension);
        }

        return $this->getBasename();
    }

    /**
     * Get the (relative) path to the file.
     *
     * @return string
     */
    public function getPathname(): string
    {
        return $this->pathName;
    }

    /**
     * Get the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->pathName, PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getFilesystemPathname(): string
    {
        return $this->baseDir.$this->pathName;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getPathname();
    }
}
