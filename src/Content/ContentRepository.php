<?php

namespace ZeroGravity\Cms\Content;

use Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Throwable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Exception\ZeroGravityException;

final class ContentRepository implements ReadablePageRepository, WritablePageRepository, CacheablePageRepository
{
    private const ALL_PAGES_CACHE_KEY = 'all_pages';

    /**
     * @var ReadablePage[]
     */
    private ?array $pages = null;

    /**
     * @var ReadablePage[]
     */
    private ?array $pagesByPath = null;

    /**
     * This is the main repository handling page loading and caching.
     */
    public function __construct(
        private readonly StructureMapper $mapper,
        private readonly AdapterInterface $cache,
        private readonly bool $skipCache,
    ) {
    }

    /**
     * Clear the complete page cache.
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Parse filesystem to get all page data.
     *
     * @return ReadablePage[]
     */
    private function loadFromParser(): array
    {
        return $this->mapper->parse();
    }

    /**
     * Fetch pages if not already loaded.
     */
    private function fetchPages(): void
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
    private function loadPagesFromCache(): bool
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
        } catch (Throwable) {
            return false;
        }
    }

    private function refreshCache(): void
    {
        try {
            $item = $this->cache->getItem(self::ALL_PAGES_CACHE_KEY);
        } catch (PsrInvalidArgumentException) {
            return;
        }
        $item->set($this->pages);
        $this->cache->save($item);
    }

    /**
     * @param ReadablePage[] $pages
     */
    private function flattenPages(array $pages): void
    {
        $this->pagesByPath = [];
        $this->doFlattenPages($pages);
    }

    /**
     * @param ReadablePage[] $pages
     */
    private function doFlattenPages(array $pages): void
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
    public function getPageTree(): array
    {
        $this->fetchPages();

        return $this->pages;
    }

    /**
     * Get all pages as flattened array, indexed by full path. This will include unpublished pages.
     *
     * @return ReadablePage[]
     */
    public function getAllPages(): array
    {
        $this->fetchPages();

        return $this->pagesByPath;
    }

    public function getPage(string $path): ?ReadablePage
    {
        $this->fetchPages();

        return $this->pagesByPath[$path] ?? null;
    }

    /**
     * Get a PageFinder instance covering the full page tree, excluding unpublished pages by default.
     */
    public function getPageFinder(): PageFinder
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
     * @throws StructureException|ZeroGravityException
     */
    public function saveChanges(PageDiff $diff): void
    {
        $this->mapper->saveChanges($diff);
    }
}
