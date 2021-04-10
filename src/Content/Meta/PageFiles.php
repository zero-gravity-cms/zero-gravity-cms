<?php

namespace ZeroGravity\Cms\Content\Meta;

use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileTypeDetector;

class PageFiles
{
    /**
     * @var File[]
     */
    private array $files;

    public function __construct(array $files, array $fileAliases = [])
    {
        Assert::allIsInstanceOf($files, File::class);
        $this->files = $files;
        $this->applyFileAliases($fileAliases);
    }

    private function applyFileAliases(array $fileAliases): void
    {
        foreach ($fileAliases as $from => $to) {
            if (isset($this->files[$to])) {
                $this->files[$from] = $this->files[$to];
            }
        }
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->files);
    }

    /**
     * Get a single setting value or a default, if not defined.
     *
     * @param mixed|null $default
     */
    public function get(string $filename, $default = null): ?File
    {
        if ($this->has($filename)) {
            return $this->files[$filename];
        }

        return $default;
    }

    /**
     * Get all files for the given type.
     *
     * @return File[]
     */
    private function getFilesByType(string $type): array
    {
        return array_filter($this->files, fn (File $file) => $file->getType() === $type);
    }

    /**
     * Get names/aliases for all available image files.
     *
     * @return File[]
     */
    public function getImages(): array
    {
        return $this->getFilesByType(FileTypeDetector::TYPE_IMAGE);
    }

    /**
     * Get names/aliases and paths for all available document files.
     *
     * @return File[]
     */
    public function getDocuments(): array
    {
        return $this->getFilesByType(FileTypeDetector::TYPE_DOCUMENT);
    }

    /**
     * Get path to single markdown file if available.
     */
    public function getMarkdownFile(): ?File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * Get path to single YAML file if available.
     */
    public function getYamlFile(): ?File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_YAML);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * Get path to single Twig file if available.
     */
    public function getTwigFile(): ?File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_TWIG);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * @return File[]
     */
    public function toArray(): array
    {
        return $this->files;
    }
}
