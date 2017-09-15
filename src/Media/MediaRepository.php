<?php

namespace ZeroGravity\Cms\Media;

use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Path\Resolver\PathResolver;

class MediaRepository
{
    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var string
     */
    private $path;

    /**
     * @var PathResolver
     */
    private $pathResolver;

    public function __construct(ContentRepository $contentRepository, string $basePath, PathResolver $pathResolver)
    {
        $this->contentRepository = $contentRepository;
        $this->path = $basePath;
        $this->pathResolver = $pathResolver;
    }

    public function getFile($relativePath)
    {
        $file = $this->pathResolver->get($relativePath);
        if (null === $file) {
            return null;
        }
    }
}
