<?php

namespace ZeroGravity\Cms\Content;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Meta\PageFiles;
use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Path\Path;

class Page
{
    const SORTING_PREFIX_PATTERN = '/^[0-9]+\.(.*)/';

    /**
     * @var string
     */
    private $name;

    /**
     * @var PageSettings
     */
    private $settings;

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
    public function __construct(string $name, array $settings = [], Page $parent = null)
    {
        $this->name = $name;
        $this->settings = new PageSettings($settings, $name);
        $this->parent = $parent;
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
     * @param string $name
     *
     * @return mixed
     */
    public function getSetting(string $name)
    {
        return $this->settings->get($name);
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

    private function buildPath(): void
    {
        if (null === $this->parent) {
            $this->path = new Path('/'.$this->getSlug());
        } else {
            $this->path = new Path(rtrim($this->parent->getPath()->toString(), '/').'/'.$this->getSlug());
        }
    }

    /**
     * @return Page|null
     */
    public function getParent(): ? Page
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
     * @return string
     */
    public function getNameWithoutSortingPrefix(): string
    {
        if (preg_match(self::SORTING_PREFIX_PATTERN, $this->getName(), $matches)) {
            return $matches[1];
        }

        return $this->getName();
    }

    /**
     * @return Path
     */
    public function getFilesystemPath(): Path
    {
        return $this->filesystemPath;
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

    /**
     * @return string
     */
    public function getTitle(): ? string
    {
        return (string) $this->getSetting('title');
    }

    /**
     * @return string|null
     */
    public function getContent(): ? string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return (string) $this->getSetting('slug');
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings->toArray();
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return (array) $this->getSetting('extra');
    }

    /**
     * @return string
     */
    public function getMenuId(): string
    {
        return (string) $this->getSetting('menu_id');
    }

    /**
     * @return string|null
     */
    public function getMenuLabel(): ? string
    {
        if (!empty($this->getSetting('menu_label'))) {
            return (string) $this->getSetting('menu_label');
        }
        if (!empty($this->getTitle())) {
            return $this->getTitle();
        }

        return $this->getNameWithoutSortingPrefix();
    }

    /**
     * Page is listed in menus.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return (bool) $this->getSetting('is_visible');
    }

    /**
     * Page is considered a modular snippet, not a standalone page.
     *
     * @return bool
     */
    public function isModular(): bool
    {
        return (bool) $this->getSetting('is_modular');
    }

    /**
     * Get custom template name to use for this page.
     *
     * @return string|null
     */
    public function getTemplate(): ? string
    {
        return (string) $this->getSetting('template');
    }

    /**
     * Get custom controller name to use for this page.
     *
     * @return string|null
     */
    public function getController(): ? string
    {
        return (string) $this->getSetting('controller');
    }

    /**
     * @param Page $childPage
     */
    public function addChild(Page $childPage): void
    {
        $this->children[] = $childPage;
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

    /**
     * Page is considered a modular snippet, not a standalone page.
     *
     * @return DateTimeImmutable
     */
    public function getPublishedAt(): ? DateTimeImmutable
    {
        return $this->getSetting('published_at');
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return null === $this->getPublishedAt() || $this->getPublishedAt()->format('U') > time();
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getExtraValue(string $name, $default = null)
    {
        $extra = $this->getExtra();
        if (array_key_exists($name, $extra)) {
            return $extra[$name];
        }

        return $default;
    }
}
