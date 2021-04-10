<?php

namespace ZeroGravity\Cms\Filesystem;

use LogicException;
use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser as FrontYAMLParser;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Filesystem\Event\AfterFileWrite;
use ZeroGravity\Cms\Filesystem\Event\BeforeFileWrite;

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

    private SplFileInfo $directoryInfo;

    private FileFactory $fileFactory;

    private ?string $parentPath = null;

    /**
     * @var File[]
     */
    private ?array $files = null;

    /**
     * @var Directory[]
     */
    private ?array $directories = null;

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        SplFileInfo $directoryInfo,
        FileFactory $fileFactory,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        string $parentPath = null
    ) {
        $this->directoryInfo = $directoryInfo;
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->parentPath = $parentPath;
        $this->parseFiles();
        $this->parseDirectories();
    }

    /**
     * Parse this directory for files.
     */
    private function parseFiles()
    {
        $this->logger->debug("Scanning directory {$this->getPath()} for files");
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
        $this->logger->debug("Scanning directory {$this->getPath()} for sub directories");
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
                $this->logger,
                $this->eventDispatcher,
                $this->getPath()
            );
        }
    }

    /**
     * Get directory path relative to parsing base path.
     */
    public function getPath(): string
    {
        if (null === $this->parentPath) {
            // don't include name of top level directory in the path
            return '';
        }

        return $this->parentPath.'/'.$this->getName();
    }

    public function getName(): string
    {
        return $this->directoryInfo->getFilename();
    }

    public function getFilesystemPathname(): string
    {
        return $this->directoryInfo->getPathname();
    }

    public function getSlug(): string
    {
        if (preg_match(self::SORTING_PREFIX_PATTERN, $this->getName(), $matches)) {
            return $matches[1];
        }

        return $this->getName();
    }

    /**
     * @return File[]
     */
    public function getFilesByType(string $type)
    {
        return array_filter($this->getFiles(), fn (File $file) => $file->getType() === $type);
    }

    public function hasSortingPrefix(): bool
    {
        return (bool) preg_match(self::SORTING_PREFIX_PATTERN, $this->getName());
    }

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
            throw StructureException::yamlAndMarkdownFilesMismatch($this->directoryInfo, $this->getYamlFile(), $this->getMarkdownFile());
        }
    }

    private function yamlAndMarkdownBasenamesMatch(): bool
    {
        return
            !$this->hasYamlFile() ||
            !$this->hasMarkdownFile() ||
            ($this->getYamlFile()->getDefaultBasename() === $this->getMarkdownFile()->getDefaultBasename())
        ;
    }

    public function getYamlFile(): ?File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_YAML);

        return count($files) ? current($files) : null;
    }

    public function hasYamlFile(): bool
    {
        return null !== $this->getYamlFile();
    }

    public function getMarkdownFile(): ?File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);

        return count($files) ? current($files) : null;
    }

    public function hasMarkdownFile(): bool
    {
        return null !== $this->getMarkdownFile();
    }

    /**
     * Get default basename defined by YAML, Markdown file or directory slug.
     */
    public function getDefaultBasename(): string
    {
        if ($this->hasYamlFile()) {
            return $this->getYamlFile()->getDefaultBasename();
        }
        if ($this->hasMarkdownFile()) {
            return $this->getMarkdownFile()->getDefaultBasename();
        }

        return $this->getSlug();
    }

    public function getDefaultBasenameTwigFile(): ?File
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
     * @return string|null
     */
    public function fetchPageContent(bool $convertMarkdown)
    {
        if ($this->hasMarkdownFile()) {
            return trim($this->getFrontYAMLDocument($convertMarkdown)->getContent());
        }

        return null;
    }

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
        }
        if ($this->hasMarkdownFile()) {
            return $this->getFrontYAMLDocument(false)->getYAML() ?: [];
        }

        return [];
    }

    /**
     * Save the given raw content to the filesystem.
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
     */
    public function saveSettings(array $newSettings): void
    {
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
     */
    public function renameOrMove(string $newRealPath): void
    {
        $this->logger->debug("Moving directory {$this->getFilesystemPathname()} to $newRealPath");
        $fs = new Filesystem();
        $fs->rename($this->getFilesystemPathname(), $newRealPath, false);

        $this->directoryInfo = new SplFileInfo($newRealPath);
        $this->parseFiles();
        $this->parseDirectories();
    }

    /**
     * Get the content strategy for this directory, one of the Directory::CONTENT_STRATEGY_* constants.
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
        $this->logger->debug("Updating markdown file in directory {$this->getPath()}");
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

        $this->writeFile($this->getMarkdownFile()->getFilesystemPathname(), $newRawContent);
    }

    private function createMarkdown($newRawContent)
    {
        $this->logger->debug("Creating new markdown file in directory {$this->getPath()}");
        $path = sprintf('%s/%s.md',
            $this->directoryInfo->getPathname(),
            $this->getDefaultBasename()
        );

        $this->writeFile($path, $newRawContent);
        $this->parseFiles();
    }

    private function updateYaml($newYaml)
    {
        $this->logger->debug("Updating YAML config in directory {$this->getPath()}");
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
            throw new LogicException('Cannot update YAML when there is neither a YAML nor a markdown file');
        }

        $this->writeFile($file->getFilesystemPathname(), $newYaml);
    }

    private function createYaml($newYaml)
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
        $yamlContent = Yaml::dump($settings, 4);

        return $yamlContent;
    }

    private function writeFile(string $realPath, string $content)
    {
        /* @var $handledEvent BeforeFileWrite */
        $handledEvent = $this->eventDispatcher->dispatch(new BeforeFileWrite($realPath, $content, $this));
        $content = $handledEvent->getContent();

        $this->logger->info("Writing to file {$realPath} for directory {$this->getPath()}");
        file_put_contents($realPath, $content);

        $this->eventDispatcher->dispatch(new AfterFileWrite($realPath, $content, $this));
    }
}
