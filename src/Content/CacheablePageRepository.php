<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content;

interface CacheablePageRepository
{
    /**
     * Clear the complete page cache.
     */
    public function clearCache(): void;
}
