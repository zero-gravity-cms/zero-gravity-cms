<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Cocur\Slugify\SlugifyInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

final class CachingResolver extends AbstractResolver implements MultiPathResolver
{
    use MultiPathFindOneTrait;

    public function __construct(
        protected readonly CacheInterface $cache,
        protected readonly SinglePathResolver $wrappedResolver,
        protected readonly SlugifyInterface $slugify,
    ) {
    }

    /**
     * Resolve the given path or glob pattern and find the matching files.
     *
     * @return File[]
     *
     * @throws InvalidArgumentException
     */
    public function find(Path $path, Path $parentPath = null): array
    {
        if (!$this->wrappedResolver instanceof MultiPathResolver) {
            return [];
        }
        $key = $this->generateCacheKey('find', $path, $parentPath);

        return $this->cache->get($key, fn (): array => $this->wrappedResolver->find($path, $parentPath));
    }

    /**
     * Resolve the given file name and path.
     *
     * @throws InvalidArgumentException
     */
    public function get(Path $path, Path $parentPath = null): ?File
    {
        $key = $this->generateCacheKey('get', $path, $parentPath);

        return $this->cache->get($key, fn (): ?File => $this->wrappedResolver->get($path, $parentPath));
    }

    private function generateCacheKey(string $method, Path $path, Path $parentPath = null): string
    {
        $parentString = $parentPath instanceof Path ? $parentPath->toString() : '';
        $signature = sprintf('%s::%s::%s', $method, $path, $parentString);

        return $this->slugify->slugify($signature).'_'.sha1($signature);
    }
}
