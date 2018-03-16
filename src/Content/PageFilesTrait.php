<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageFiles;

trait PageFilesTrait
{
    /**
     * @var PageFiles
     */
    private $files;

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

    /**
     * @param string $filename
     *
     * @return null|File
     */
    public function getFile(string $filename): ? File
    {
        return $this->files->get($filename);
    }

    /**
     * Get names/aliases for all available image files.
     *
     * @return File[]
     */
    public function getImages(): array
    {
        return $this->files->getImages();
    }

    /**
     * Get names/aliases and paths for all available document files.
     *
     * @return File[]
     */
    public function getDocuments(): array
    {
        return $this->files->getDocuments();
    }

    /**
     * Get path to single markdown file if available.
     *
     * @return File|null
     */
    public function getMarkdownFile(): ? File
    {
        return $this->files->getMarkdownFile();
    }

    /**
     * Get path to single YAML file if available.
     *
     * @return File|null
     */
    public function getYamlFile(): ? File
    {
        return $this->files->getYamlFile();
    }

    /**
     * Get path to single Twig file if available.
     *
     * @return File|null
     */
    public function getTwigFile(): ? File
    {
        return $this->files->getTwigFile();
    }
}
