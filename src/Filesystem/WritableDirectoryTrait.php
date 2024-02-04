<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Filesystem;

use LogicException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Filesystem\Event\AfterFileWrite;
use ZeroGravity\Cms\Filesystem\Event\BeforeFileWrite;

trait WritableDirectoryTrait
{
    /**
     * Save the given raw content to the filesystem.
     */
    public function saveContent(string $newRawContent = null): void
    {
        match ($this->getContentStrategy()) {
            self::CONTENT_STRATEGY_YAML_AND_MARKDOWN,
            self::CONTENT_STRATEGY_MARKDOWN_ONLY => $this->updateMarkdown($newRawContent),
            default => $this->createMarkdown($newRawContent),
        };
    }

    /**
     * Save the given settings array to the filesystem.
     */
    public function saveSettings(array $newSettings): void
    {
        $newYaml = $this->dumpSettingsToYaml($newSettings);

        match ($this->getContentStrategy()) {
            self::CONTENT_STRATEGY_YAML_ONLY,
            self::CONTENT_STRATEGY_MARKDOWN_ONLY,
            self::CONTENT_STRATEGY_YAML_AND_MARKDOWN => $this->updateYaml($newYaml),
            default => $this->createYaml($newYaml),
        };
    }

    /**
     * Rename/move the directory to another path.
     */
    public function renameOrMove(string $newRealPath): void
    {
        $this->logger->debug("Moving directory {$this->getFilesystemPathname()} to {$newRealPath}");
        $fs = new Filesystem();
        $fs->rename($this->getFilesystemPathname(), $newRealPath, false);

        $this->directoryInfo = new SplFileInfo($newRealPath);
        $this->parseFiles();
        $this->parseDirectories();
    }

    private function updateMarkdown($newRawContent): void
    {
        $this->logger->debug("Updating markdown file in directory {$this->getPath()}");
        $document = $this->getFrontYAMLDocument(false);
        if (is_array($document->getYAML())) {
            $yamlContent = $this->dumpSettingsToYaml($document->getYAML());
            $newRawContent = <<<FRONTMATTER
---
{$yamlContent}
---
{$newRawContent}
FRONTMATTER;
        }

        $this->writeFile($this->getMarkdownFile()->getFilesystemPathname(), $newRawContent);
    }

    private function createMarkdown($newRawContent): void
    {
        $this->logger->debug("Creating new markdown file in directory {$this->getPath()}");
        $path = sprintf('%s/%s.md',
            $this->directoryInfo->getPathname(),
            $this->getDefaultBasename()
        );

        $this->writeFile($path, $newRawContent);
        $this->parseFiles();
    }

    private function updateYaml($newYaml): void
    {
        $this->logger->debug("Updating YAML config in directory {$this->getPath()}");
        $file = $this->getYamlFile();
        if (null !== $file) {
            $this->writeFile($file->getFilesystemPathname(), $newYaml);

            return;
        }

        $file = $this->getMarkdownFile();
        if (null === $file) {
            throw new LogicException('Cannot update YAML when there is neither a YAML nor a markdown file');
        }

        $document = $this->getFrontYAMLDocument(false);
        $newYaml = <<<FRONTMATTER
---
{$newYaml}
---
{$document->getContent()}
FRONTMATTER;

        $this->writeFile($file->getFilesystemPathname(), $newYaml);
    }

    private function createYaml($newYaml): void
    {
        $this->logger->debug("Creating new YAML config in directory {$this->getPath()}");
        $path = sprintf('%s/%s.yaml',
            $this->directoryInfo->getPathname(),
            $this->getDefaultBasename()
        );

        $this->writeFile($path, $newYaml);
        $this->parseFiles();
    }

    private function dumpSettingsToYaml(array $settings): string
    {
        return Yaml::dump($settings, 4);
    }

    private function writeFile(string $realPath, string $content): void
    {
        /* @var $handledEvent BeforeFileWrite */
        $handledEvent = $this->eventDispatcher->dispatch(new BeforeFileWrite($realPath, $content, $this));
        $content = $handledEvent->getContent();

        $this->logger->info("Writing to file {$realPath} for directory {$this->getPath()}");
        file_put_contents($realPath, $content);

        $this->eventDispatcher->dispatch(new AfterFileWrite($realPath, $content, $this));
    }
}
