<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageFiles;

trait PageFilesTrait
{
    private ?PageFiles $files = null;

    /**
     * @param File[] $files
     */
    public function setFiles(array $files): void
    {
        $this->files = new PageFiles($files, $this->getSetting('file_aliases'));
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files->toArray();
    }

    public function getFile(string $filename): ?File
    {
        return $this->files->get($filename);
    }

    /**
     * Get representations for all available image files.
     *
     * @return File[]
     */
    public function getImages(): array
    {
        return $this->files->getImages();
    }

    /**
     * Get representations for all available document files.
     *
     * @return File[]
     */
    public function getDocuments(): array
    {
        return $this->files->getDocuments();
    }

    /**
     * Get single markdown file representation if available.
     */
    public function getMarkdownFile(): ?File
    {
        return $this->files->getMarkdownFile();
    }

    /**
     * Get single YAML file representation if available.
     */
    public function getYamlFile(): ?File
    {
        return $this->files->getYamlFile();
    }

    /**
     * Get single Twig file representation if available.
     */
    public function getTwigFile(): ?File
    {
        return $this->files->getTwigFile();
    }
}
