<?php

namespace ZeroGravity\Cms\Content;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ZeroGravity\Cms\Content\Finder\PageFinder;

class ContentRepository
{
    const ALL_PAGES_CACHE_KEY = 'all_pages';

    /**
     * @var ReadablePage[]
     */
    protected $pages;

    /**
     * @var ReadablePage[]
     */
    protected $pagesByPath;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var bool
     */
    private $skipCache;

    /**
     * @var StructureMapper
     */
    private $parser;

    /**
     * This is the main repository handling page loading and caching.
     *
     * @param StructureMapper $parser
     * @param CacheInterface  $cache
     * @param bool            $skipCache
     */
    public function __construct(StructureMapper $parser, CacheInterface $cache, bool $skipCache)
    {
        $this->parser = $parser;
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
        $pages = $this->parser->parse();

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
     *
     * @return bool
     */
    protected function loadPagesFromCache(): bool
    {
        try {
            if (!$this->skipCache && $this->cache->has(self::ALL_PAGES_CACHE_KEY)) {
                $this->pages = $this->cache->get(self::ALL_PAGES_CACHE_KEY);
                $this->flattenPages($this->pages);

                return true;
            }
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return false;
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function refreshCache()
    {
        $this->cache->set(self::ALL_PAGES_CACHE_KEY, $this->pages);
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
            $this->doFlattenPages($page->getChildren()->toArray());
        }
    }

    /**
     * Get pages as nested tree.
     *
     * @return ReadablePage[]
     */
    public function getPageTree()
    {
        $this->fetchPages();

        return $this->pages;
    }

    /**
     * Get all pages as flattened array, indexed by full path.
     *
     * @return ReadablePage[]
     */
    public function getAllPages()
    {
        $this->fetchPages();

        return $this->pagesByPath;
    }

    /**
     * @param string $path
     *
     * @return null|ReadablePage
     */
    public function getPage(string $path): ? ReadablePage
    {
        $this->fetchPages();
        if (isset($this->pagesByPath[$path])) {
            return $this->pagesByPath[$path];
        }

        return null;
    }

    /**
     * @return PageFinder
     */
    public function getPageFinder()
    {
        return PageFinder::create()->inPageList($this->getPageTree());
    }

    /**
     * @param ReadablePage $page
     *
     * @return WritablePage
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage
    {
        return $this->parser->getWritablePageInstance($page);
    }
}
