<?php

namespace ZeroGravity\Cms\Content\Meta;

use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileTypes;

final class PageFiles
{
    /**
     * @param array<string, File>   $files
     * @param array<string, string> $fileAliases
     */
    public function __construct(
        private array $files,
        array $fileAliases = [],
    ) {
        Assert::allIsInstanceOf($this->files, File::class);
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
     */
    public function get(string $filename, mixed $default = null): ?File
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
        return array_filter($this->files, static fn (File $file): bool => $file->getType() === $type);
    }

    /**
     * Get names/aliases for all available image files.
     *
     * @return File[]
     */
    public function getImages(): array
    {
        return $this->getFilesByType(FileTypes::TYPE_IMAGE);
    }

    /**
     * Get names/aliases and paths for all available document files.
     *
     * @return File[]
     */
    public function getDocuments(): array
    {
        return $this->getFilesByType(FileTypes::TYPE_DOCUMENT);
    }

    /**
     * Get path to single markdown file if available.
     */
    public function getMarkdownFile(): ?File
    {
        $files = $this->getFilesByType(FileTypes::TYPE_MARKDOWN);
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
        $files = $this->getFilesByType(FileTypes::TYPE_YAML);
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
        $files = $this->getFilesByType(FileTypes::TYPE_TWIG);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * @return array<string, File>
     */
    public function toArray(): array
    {
        return $this->files;
    }
}
