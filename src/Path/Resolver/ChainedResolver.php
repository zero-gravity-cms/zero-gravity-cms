<?php

namespace ZeroGravity\Cms\Path\Resolver;

use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;

final class ChainedResolver extends AbstractResolver
{
    /**
     * @var AbstractResolver[]
     */
    private array $resolvers;

    /**
     * @param AbstractResolver[] $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Resolve the given file name and path.
     */
    public function get(Path $path, Path $parentPath = null): ?File
    {
        foreach ($this->resolvers as $resolver) {
            $found = $resolver->get($path, $parentPath);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }
}
