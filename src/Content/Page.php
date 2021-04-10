<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Meta\PagePublishingTrait;
use ZeroGravity\Cms\Content\Meta\PageSettingsTrait;
use ZeroGravity\Cms\Content\Meta\PageTaxonomyTrait;
use ZeroGravity\Cms\Path\Path;

class Page implements ReadablePage
{
    use PageFilesTrait;
    use PagePublishingTrait;
    use PageSettingsTrait;
    use PageTaxonomyTrait;

    public const SORTING_PREFIX_PATTERN = '/^[0-9]+\.(.*)/';

    public const TAXONOMY_TAG = 'tag';
    public const TAXONOMY_CATEGORY = 'category';
    public const TAXONOMY_AUTHOR = 'author';

    protected string $name;
    private ?ReadablePage $parent = null;
    private ?string $content = null;

    /**
     * @var Page[]
     */
    private array $children = [];

    private ?Path $path = null;
    private ?Path $filesystemPath = null;

    public function __construct(string $name, array $settings = [], ReadablePage $parent = null)
    {
        $this->name = $name;
        $this->initSettings($settings, $name);
        $this->initParent($parent);
        $this->init();
    }

    protected function init(): void
    {
        $this->setFiles([]);
    }

    /**
     * Set parent page and initialize all dependent values.
     */
    protected function initParent(ReadablePage $parent = null): void
    {
        $this->parent = $parent;
        $this->buildFilesystemPath();
        $this->buildPath();
        if (null !== $this->parent) {
            $this->parent->addChild($this);
        }
    }

    /**
     * @return Page|null
     */
    public function getParent(): ?ReadablePage
    {
        return $this->parent;
    }

    public function setContent(string $content = null): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilesystemPath(): Path
    {
        return $this->filesystemPath;
    }

    /**
     * @param Page $childPage
     */
    public function addChild(self $childPage): void
    {
        $this->children[$childPage->getPath()->toString()] = $childPage;
    }

    public function getChildren(): PageFinder
    {
        return PageFinder::create()
            ->inPageList($this->children)
            ->depth(0)
        ;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Build the URL path. This needs to be triggered when the name, slug or parent is changed.
     */
    protected function buildPath(): void
    {
        if (null === $this->parent) {
            $this->path = new Path('/'.$this->getSlug());
        } else {
            $this->path = new Path(rtrim($this->parent->getPath()->toString(), '/').'/'.$this->getSlug());
        }
    }

    /**
     * Build the URL path. This needs to be triggered when the name or parent is changed.
     */
    protected function buildFilesystemPath(): void
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
