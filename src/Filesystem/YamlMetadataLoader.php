<?php

namespace ZeroGravity\Cms\Filesystem;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\Meta\Metadata;
use ZeroGravity\Cms\Content\Meta\MetadataLoader;

class YamlMetadataLoader implements MetadataLoader
{
    /**
     * Load and return the metadata for the given file.
     *
     * @param string $pathname
     * @param string $basePath
     *
     * @return Metadata
     */
    public function loadMetadataForFile(string $pathname, string $basePath): Metadata
    {
        $metadataPath = sprintf('%s/%s.meta.yaml', rtrim($basePath, '/'), $pathname);
        if (!is_file($metadataPath)) {
            return new Metadata([]);
        }

        try {
            $data = Yaml::parse(file_get_contents($metadataPath));
        } catch (ParseException $e) {
            $data = [];
        }

        if (!is_array($data)) {
            $data = [
                'title' => $data,
            ];
        }

        return new Metadata($data);
    }
}
