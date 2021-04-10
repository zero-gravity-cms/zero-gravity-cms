<?php

namespace ZeroGravity\Cms\Content;

use Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Throwable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Exception\ZeroGravityException;

class ContentRepository
{
    const ALL_PAGES_CACHE_KEY = 'all_pages';

    /**
     * @var ReadablePage[]
     */
    protected ?array $pages = null;

    /**
     * @var ReadablePage[]
     */
    protected ?array $pagesByPath = null;

    private AdapterInterface $cache;

    private bool $skipCache;

    private StructureMapper $mapper;

    /**
     * This is the main repository handling page loading and caching.
     */
    public function __construct(StructureMapper $mapper, AdapterInterface $cache, bool $skipCache)
    {
        $this->mapper = $mapper;
        $this->cache = $cache;
        $this->skipCache = $skipCache;
    }

    /**
     * Clear the complete page cache.
     */
    public function clearCache()
    {
        $this->cache->clear();
    }

    /**
     * Parse filesystem to get all page data.
     *
     * @return ReadablePage[]
     */
    protected function loadFromParser()
    {
        $pages = $this->mapper->parse();

        return $pages;
    }

    /**
     * Fetch pages if not already loaded.
     */
    protected function fetchPages()
    {
        if (null === $this->pages && !$this->loadPagesFromCache()) {
            $this->pages = $this->loadFromParser();
            $this->flattenPages($this->pages);
            $this->refreshCache();
        }
    }

    /**
     * Load pages from cache if applicable and actual cached pages exist.
     */
    protected function loadPagesFromCache(): bool
    {
        if ($this->skipCache) {
            return false;
        }

        try {
            $item = $this->cache->getItem(self::ALL_PAGES_CACHE_KEY);
            if (!$item->isHit()) {
                return false;
            }
            $this->pages = $item->get();
            $this->flattenPages($this->pages);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function refreshCache(): void
    {
        try {
            $item = $this->cache->getItem(self::ALL_PAGES_CACHE_KEY);
        } catch (PsrInvalidArgumentException $e) {
            return;
        }
        $item->set($this->pages);
        $this->cache->save($item);
    }

    /**
     * @param ReadablePage[] $pages
     */
    protected function flattenPages(array $pages)
    {
        $this->pagesByPath = [];
        $this->doFlattenPages($pages);
    }

    /**
     * @param ReadablePage[] $pages
     */
    protected function doFlattenPages(array $pages)
    {
        foreach ($pages as $page) {
            $this->pagesByPath[$page->getPath()->toString()] = $page;
            $this->doFlattenPages($page->getChildren()->published(null)->toArray());
        }
    }

    /**
     * Get pages as nested tree. This will include unpublished pages.
     *
     * @return ReadablePage[]
     */
    public function getPageTree()
    {
        $this->fetchPages();

        return $this->pages;
    }

    /**
     * Get all pages as flattened array, indexed by full path. This will include unpublished pages.
     *
     * @return ReadablePage[]
     */
    public function getAllPages()
    {
        $this->fetchPages();

        return $this->pagesByPath;
    }

    public function getPage(string $path): ?ReadablePage
    {
        $this->fetchPages();
        if (isset($this->pagesByPath[$path])) {
            return $this->pagesByPath[$path];
        }

        return null;
    }

    /**
     * Get a PageFinder instance covering the full page tree, excluding unpublished pages by default.
     *
     * @return PageFinder
     */
    public function getPageFinder()
    {
        return PageFinder::create()->inPageList($this->getPageTree());
    }

    /**
     * Get writable instance of an existing page.
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage
    {
        return $this->mapper->getWritablePageInstance($page);
    }

    /**
     * Get new writable page instance.
     */
    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage
    {
        return $this->mapper->getNewWritablePage($parentPage);
    }

    /**
     * Store changes of the given page diff.
     *
     * @throws ZeroGravityException
     */
    public function saveChanges(PageDiff $diff)
    {
        $this->mapper->saveChanges($diff);
    }
}
