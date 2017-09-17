<?php

namespace ZeroGravity\Cms\Content\Meta;

interface MetadataLoader
{
    /**
     * Load and return the metadata for the given file.
     *
     * @param string $pathname
     * @param string $basePath
     *
     * @return Metadata
     */
    public function loadMetadataForFile(string $pathname, string $basePath): Metadata;
}
