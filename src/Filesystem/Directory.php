<?php

namespace ZeroGravity\Cms\Filesystem;

use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser as FrontYAMLParser;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\StructureException;

class Directory
{
    const CONFIG_TYPE_YAML = 'yaml';
    const CONFIG_TYPE_FRONTMATTER = 'frontmatter';
    const SORTING_PREFIX_PATTERN = '/^[0-9]+\.(.*)/';
    const MODULAR_PREFIX_PATTERN = '/^_(.*)/';

    /**
     * This directory does not hold page content.
     */
    const CONTENT_STRATEGY_NONE = 'none';

    /**
     * Single YAML file only.
     */
    const CONTENT_STRATEGY_YAML_ONLY = 'yaml_only';

    /**
     * Single markdown file with optional frontmatter YAML config.
     */
    const CONTENT_STRATEGY_MARKDOWN_ONLY = 'markdown_only';

    /**
     * Only twig files.
     */
    const CONTENT_STRATEGY_TWIG_ONLY = 'twig_only';

    /**
     * YAML config file and markdown content file.
     */
    const CONTENT_STRATEGY_YAML_AND_MARKDOWN = 'yaml_and_markdown';

    /**
     * @var SplFileInfo
     */
    private $directoryInfo;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var string|null
     */
    private $parentPath;

    /**
     * @var File[]
     */
    private $files;

    /**
     * @var Directory[]
     */
    private $directories;

    /**
     * @param SplFileInfo $directoryInfo
     * @param FileFactory $fileFactory
     * @param string|null $parentPath
     */
    public function __construct(
        SplFileInfo $directoryInfo,
        FileFactory $fileFactory,
        string $parentPath = null
    ) {
        $this->directoryInfo = $directoryInfo;
        $this->fileFactory = $fileFactory;
        $this->parentPath = $parentPath;
        $this->parseFiles();
        $this->parseDirectories();
    }

    /**
     * Parse this directory for files.
     */
    private function parseFiles()
    {
        $fileFinder = Finder::create()
            ->files()
            ->depth(0)
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->notName('*.meta.yaml')
            ->sortByName()
            ->in($this->directoryInfo->getRealPath())
        ;

        $this->files = [];
        foreach ($fileFinder as $fileInfo) {
            /* @var $fileInfo FinderSplFileInfo */
            $filePath = $this->getPath().'/'.$fileInfo->getRelativePathname();
            $this->files[$fileInfo->getFilename()] = $this->fileFactory->createFile($filePath);
        }
    }

    /**
     * Parse this directory for sub directories.
     */
    private function parseDirectories()
    {
        $subDirectoryFinder = Finder::create()
            ->directories()
            ->depth(0)
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->sortByName()
            ->in($this->directoryInfo->getRealPath())
        ;

        $this->directories = [];
        foreach ($subDirectoryFinder as $directoryInfo) {
            /* @var $directoryInfo FinderSplFileInfo */
            $this->directories[$directoryInfo->getFilename()] = new self(
                $directoryInfo,
                $this->fileFactory,
                $this->getPath()
            );
        }
    }

    /**
     * Get directory path relative to parsing base path.
     *
     * @return string
     */
    public function getPath(): string
    {
        if (null === $this->parentPath) {
            // don't include name of top level directory in the path
            return '';
        }

        return $this->parentPath.'/'.$this->getName();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->directoryInfo->getFilename();
    }

    /**
     * @return string
     */
    public function getFilesystemPathname(): string
    {
        return $this->directoryInfo->getPathname();
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        if (preg_match(self::SORTING_PREFIX_PATTERN, $this->getName(), $matches)) {
            return $matches[1];
        }

        return $this->getName();
    }

    /**
     * @param string $type
     *
     * @return File[]
     */
    public function getFilesByType(string $type)
    {
        return array_filter($this->getFiles(), function (File $file) use ($type) {
            return $file->getType() === $type;
        });
    }

    /**
     * @return bool
     */
    public function hasSortingPrefix(): bool
    {
        return (bool) preg_match(self::SORTING_PREFIX_PATTERN, $this->getName());
    }

    /**
     * @return bool
     */
    public function hasUnderscorePrefix(): bool
    {
        return (bool) preg_match(self::MODULAR_PREFIX_PATTERN, $this->getName());
    }

    /**
     * Validate the file structure inside this directory.
     *
     * @throws StructureException
     */
    public function validateFiles()
    {
        $this->validateFileCounts();
        $this->validateBasenames();
    }

    /**
     * Validate number of files of specific types in this directory.
     */
    private function validateFileCounts(): void
    {
        $yamlFiles = $this->getFilesByType(FileTypeDetector::TYPE_YAML);
        if (count($yamlFiles) > 1) {
            throw StructureException::moreThanOneYamlFile($this->directoryInfo, $yamlFiles);
        }
        $markdownFiles = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);
        if (count($markdownFiles) > 1) {
            throw StructureException::moreThanOneMarkdownFile($this->directoryInfo, $markdownFiles);
        }
    }

    /**
     * Validate basenames of YAML and markdown files.
     */
    private function validateBasenames(): void
    {
        if (!$this->yamlAndMarkdownBasenamesMatch()) {
            throw StructureException::yamlAndMarkdownFilesMismatch(
                $this->directoryInfo,
                $this->getYamlFile(),
                $this->getMarkdownFile()
            );
        }
    }

    /**
     * @return bool
     */
    private function yamlAndMarkdownBasenamesMatch(): bool
    {
        return
            !$this->hasYamlFile() ||
            !$this->hasMarkdownFile() ||
            ($this->getYamlFile()->getDefaultBasename() === $this->getMarkdownFile()->getDefaultBasename())
        ;
    }

    /**
     * @return null|File
     */
    public function getYamlFile(): ? File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_YAML);

        return count($files) ? current($files) : null;
    }

    /**
     * @return bool
     */
    public function hasYamlFile(): bool
    {
        return null !== $this->getYamlFile();
    }

    /**
     * @return null|File
     */
    public function getMarkdownFile(): ? File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);

        return count($files) ? current($files) : null;
    }

    /**
     * @return bool
     */
    public function hasMarkdownFile(): bool
    {
        return null !== $this->getMarkdownFile();
    }

    /**
     * Get default basename defined by YAML, Markdown file or directory slug.
     *
     * @return string
     */
    public function getDefaultBasename(): string
    {
        if ($this->hasYamlFile()) {
            return $this->getYamlFile()->getDefaultBasename();
        } elseif ($this->hasMarkdownFile()) {
            return $this->getMarkdownFile()->getDefaultBasename();
        }

        return $this->getSlug();
    }

    /**
     * @return null|File
     */
    public function getDefaultBasenameTwigFile(): ? File
    {
        foreach ($this->getTwigFiles() as $twigFile) {
            if ($twigFile->getBasename('.html.'.$twigFile->getExtension()) === $this->getDefaultBasename()) {
                return $twigFile;
            }
        }

        return null;
    }

    /**
     * @return File[]
     */
    public function getTwigFiles(): array
    {
        return $this->getFilesByType(FileTypeDetector::TYPE_TWIG);
    }

    /**
     * File objects indexed by filename.
     *
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Sub directories indexed by directory name.
     *
     * @return Directory[]
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Get files of this directory and all sub directories.
     *
     * @return File[]
     */
    public function getFilesRecursively(): array
    {
        $files = $this->getFiles();
        foreach ($this->getDirectories() as $directory) {
            foreach ($directory->getFilesRecursively() as $path => $file) {
                $files[$directory->getName().'/'.$path] = $file;
            }
        }

        return $files;
    }

    /**
     * @param bool $convertMarkdown
     *
     * @return null|string
     */
    public function fetchPageContent(bool $convertMarkdown)
    {
        if ($this->hasMarkdownFile()) {
            return trim($this->getFrontYAMLDocument($convertMarkdown)->getContent());
        }

        return null;
    }

    /**
     * @param bool $convertMarkdown
     *
     * @return Document
     */
    public function getFrontYAMLDocument(bool $convertMarkdown): Document
    {
        $parser = new FrontYAMLParser();
        $document = $parser->parse(
            file_get_contents($this->getMarkdownFile()->getFilesystemPathname()),
            $convertMarkdown
        );

        return $document;
    }

    /**
     * Fetch page settings from either YAML or markdown/frontmatter.
     *
     * @return array
     */
    public function fetchPageSettings()
    {
        if ($this->hasYamlFile()) {
            $data = Yaml::parse(file_get_contents($this->getYamlFile()->getFilesystemPathname()));

            return is_array($data) ? $data : [];
        } elseif ($this->hasMarkdownFile()) {
            return $this->getFrontYAMLDocument(false)->getYAML() ?: [];
        }

        return [];
    }

    /**
     * Save the given raw content to the filesystem.
     *
     * @param string|null $newRawContent
     */
    public function saveContent(string $newRawContent = null): void
    {
        switch ($this->getContentStrategy()) {
            case self::CONTENT_STRATEGY_YAML_AND_MARKDOWN:
            case self::CONTENT_STRATEGY_MARKDOWN_ONLY:
                $this->updateMarkdown($newRawContent);
                break;

            default:
                $this->createMarkdown($newRawContent);
        }
    }

    /**
     * Save the given settings array to the filesystem.
     *
     * @param array $newSettings
     */
    public function saveSettings(array $newSettings): void
    {
        // @TODO: extract default settings
        $newYaml = $this->dumpSettingsToYaml($newSettings);

        switch ($this->getContentStrategy()) {
            case self::CONTENT_STRATEGY_YAML_ONLY:
            case self::CONTENT_STRATEGY_MARKDOWN_ONLY:
            case self::CONTENT_STRATEGY_YAML_AND_MARKDOWN:
                $this->updateYaml($newYaml);
                break;

            default:
                $this->createYaml($newYaml);
        }
    }

    /**
     * Rename/move the directory to another path.
     *
     * @param string $newRealPath
     */
    public function renameOrMove(string $newRealPath): void
    {
        $fs = new Filesystem();
        $fs->rename($this->directoryInfo->getPathname(), $newRealPath, false);

        $this->directoryInfo = new SplFileInfo($newRealPath);
        $this->parseFiles();
        $this->parseDirectories();
    }

    /**
     * Get the content strategy for this directory, one of the Directory::CONTENT_STRATEGY_* constants.
     *
     * @return string
     */
    public function getContentStrategy(): string
    {
        $hasMarkdown = $this->hasMarkdownFile();
        $hasYaml = $this->hasYamlFile();
        $hasTwig = count($this->getTwigFiles()) > 0;

        if ($hasMarkdown && $hasYaml) {
            return self::CONTENT_STRATEGY_YAML_AND_MARKDOWN;
        }
        if ($hasYaml) {
            return self::CONTENT_STRATEGY_YAML_ONLY;
        }
        if ($hasMarkdown) {
            return self::CONTENT_STRATEGY_MARKDOWN_ONLY;
        }
        if ($hasTwig) {
            return self::CONTENT_STRATEGY_TWIG_ONLY;
        }

        return self::CONTENT_STRATEGY_NONE;
    }

    private function updateMarkdown($newRawContent)
    {
        $document = $this->getFrontYAMLDocument(false);
        if (is_array($document->getYAML())) {
            $yamlContent = $this->dumpSettingsToYaml($document->getYAML());
            $newRawContent = <<<FRONTMATTER
---
$yamlContent
---
$newRawContent
FRONTMATTER;
        }

        file_put_contents($this->getMarkdownFile()->getFilesystemPathname(), $newRawContent);
    }

    private function createMarkdown($newRawContent)
    {
        $path = sprintf('%s/%s.md',
            $this->directoryInfo->getPathname(),
            $this->getDefaultBasename()
        );

        file_put_contents($path, $newRawContent);
        $this->parseFiles();
    }

    private function updateYaml($newYaml)
    {
        if ($this->hasYamlFile()) {
            $file = $this->getYamlFile();
        } elseif ($this->hasMarkdownFile()) {
            $file = $this->getMarkdownFile();
            $document = $this->getFrontYAMLDocument(false);
            $newYaml = <<<FRONTMATTER
---
$newYaml
---
{$document->getContent()}
FRONTMATTER;
        } else {
            throw new \LogicException('Cannot update YAML when there is neither a YAML nor a markdown file');
        }

        file_put_contents($file->getFilesystemPathname(), $newYaml);
    }

    private function createYaml($newYaml)
    {
        $path = sprintf('%s/%s.yaml',
            $this->directoryInfo->getPathname(),
            $this->getDefaultBasename()
        );

        file_put_contents($path, $newYaml);
        $this->parseFiles();
    }

    /**
     * @param array $settings
     *
     * @return string
     */
    private function dumpSettingsToYaml(array $settings): string
    {
        $yamlContent = Yaml::dump($settings, 4);

        return $yamlContent;
    }
}
