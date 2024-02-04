<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Meta\PagePublishingTrait;
use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Content\Meta\PageSettingsTrait;
use ZeroGravity\Cms\Content\Meta\PageTaxonomyTrait;
use ZeroGravity\Cms\Path\Path;

/**
 * This trait contains settings related methods (mostly getters) of the Page class.
 * This helps to separate native properties from validated settings/options.
 *
 * @phpstan-import-type SettingValue from PageSettings
 */
class Page implements ReadablePage
{
    use PageFilesTrait;
    use PagePublishingTrait;
    use PageSettingsTrait;
    use PageTaxonomyTrait;

    final public const SORTING_PREFIX_PATTERN = '/^\d+\.(.*)/';

    final public const TAXONOMY_TAG = 'tag';
    final public const TAXONOMY_CATEGORY = 'category';
    final public const TAXONOMY_AUTHOR = 'author';
    private ReadablePage|Page|null $parent = null;
    private ?string $content = null;

    /**
     * @var array<string, Page>
     */
    private array $children = [];

    private ?Path $path = null;
    private ?Path $filesystemPath = null;

    /**
     * @param array<string, SettingValue> $settings
     */
    public function __construct(
        protected string $name,
        array $settings = [],
        ReadablePage $parent = null,
    ) {
        $this->initSettings($settings, $this->name);
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
    protected function initParent(ReadablePage|self $parent = null): void
    {
        $this->parent = $parent;
        $this->buildFilesystemPath();
        $this->buildPath();
        if ($this->parent instanceof Page) {
            $this->parent->addChild($this);
        }
    }

    public function getParent(): ReadablePage|self|null
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

    public function addChild(self $childPage): void
    {
        $this->children[$childPage->getPath()->toString()] = $childPage;
    }

    public function getChildren(): PageFinder
    {
        return PageFinder::create()
            ->inPageList($this->children)
            ->depth('== 0')
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
        if (!$this->parent instanceof ReadablePage) {
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
        if (!$this->parent instanceof ReadablePage) {
            $this->filesystemPath = new Path('/'.$this->getName());
        } else {
            $this->filesystemPath = new Path(
                rtrim($this->parent->getFilesystemPath()->toString(), '/').'/'.$this->getName()
            );
        }
    }
}
