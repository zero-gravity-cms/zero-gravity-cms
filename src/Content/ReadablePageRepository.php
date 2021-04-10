<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Finder\PageFinder;

interface ReadablePageRepository
{
    /**
     * Get pages as nested tree. This will include unpublished pages.
     *
     * @return ReadablePage[]
     */
    public function getPageTree(): array;

    /**
     * Get all pages as flattened array, indexed by full path. This will include unpublished pages.
     *
     * @return ReadablePage[]
     */
    public function getAllPages(): array;

    public function getPage(string $path): ?ReadablePage;

    /**
     * Get a PageFinder instance covering the full page tree, excluding unpublished pages by default.
     */
    public function getPageFinder(): PageFinder;
}
