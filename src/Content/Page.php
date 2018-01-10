<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Meta\PageFiles;
use ZeroGravity\Cms\Content\Meta\PageSettingsTrait;
use ZeroGravity\Cms\Path\Path;

class Page
{
    use PageSettingsTrait;

    const SORTING_PREFIX_PATTERN = '/^[0-9]+\.(.*)/';
    const TAXONOMY_TAG = 'tag';
    const TAXONOMY_CATEGORY = 'category';
    const TAXONOMY_AUTHOR = 'author';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $content;

    /**
     * @var Page[]
     */
    private $children = [];

    /**
     * @var Page
     */
    private $parent;

    /**
     * @var PageFiles
     */
    private $files;

    /**
     * @var Path
     */
    private $path;

    /**
     * @var Path
     */
    private $filesystemPath;

    /**
     * Create a new page object.
     *
     * @param string $name
     * @param array  $settings
     * @param Page   $parent
     */
    public function __construct(string $name, array $settings = [], self $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->applySettings($settings, $name);
        $this->init();
    }

    private function init()
    {
        $this->buildFilesystemPath();
        $this->buildPath();
        $this->setFiles([]);
        if (null !== $this->parent) {
            $this->parent->addChild($this);
        }
    }

    /**
     * @param string|null $content
     */
    public function setContent(string $content = null): void
    {
        $this->content = $content;
    }

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

    /**
     * @return Path
     */
    public function getPath(): Path
    {
        return $this->path;
    }

    /**
     * @return Page|null
     */
    public function getParent(): ? self
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Path
     */
    public function getFilesystemPath(): Path
    {
        return $this->filesystemPath;
    }

    /**
     * @return string|null
     */
    public function getContent(): ? string
    {
        return $this->content;
    }

    /**
     * @param Page $childPage
     */
    public function addChild(self $childPage): void
    {
        $this->children[$childPage->getPath()->toString()] = $childPage;
    }

    /**
     * @return PageFinder
     */
    public function getChildren(): PageFinder
    {
        return PageFinder::create()->inPageList($this->children);
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    private function buildPath(): void
    {
        if (null === $this->parent) {
            $this->path = new Path('/'.$this->getSlug());
        } else {
            $this->path = new Path(rtrim($this->parent->getPath()->toString(), '/').'/'.$this->getSlug());
        }
    }

    private function buildFilesystemPath(): void
    {
        if (null === $this->parent) {
            $this->filesystemPath = new Path('/'.$this->getName());
        } else {
            $this->filesystemPath = new Path(
                rtrim($this->parent->getFilesystemPath()->toString(), '/').'/'.$this->getName()
            );
        }
    }
}
