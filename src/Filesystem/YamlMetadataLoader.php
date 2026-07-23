<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Filesystem;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\Meta\Metadata;
use ZeroGravity\Cms\Content\Meta\MetadataLoader;
use ZeroGravity\Cms\Exception\FilesystemException;

final class YamlMetadataLoader implements MetadataLoader
{
    /**
     * Load and return the metadata for the given file.
     */
    public function loadMetadataForFile(string $pathname, string $basePath): Metadata
    {
        $metadataPath = sprintf('%s/%s.meta.yaml', rtrim($basePath, '/'), $pathname);
        if (!is_file($metadataPath)) {
            return new Metadata([]);
        }

        $contents = file_get_contents($metadataPath);
        if (false === $contents) {
            throw FilesystemException::cannotReadFile($metadataPath);
        }
        try {
            $data = Yaml::parse($contents);
        } catch (ParseException) {
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
