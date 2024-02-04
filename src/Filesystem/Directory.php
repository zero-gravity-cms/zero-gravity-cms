<?php

namespace ZeroGravity\Cms\Filesystem;

use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser as FrontYAMLParser;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypes;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Exception\StructureException;

final class Directory
{
    use WritableDirectoryTrait;

    public const SORTING_PREFIX_PATTERN = '/^\d+\.(.*)/';
    public const MODULAR_PREFIX_PATTERN = '/^_(.*)/';

    /**
     * This directory does not hold page content.
     */
    public const CONTENT_STRATEGY_NONE = 'none';

    /**
     * Single YAML file only.
     */
    public const CONTENT_STRATEGY_YAML_ONLY = 'yaml_only';

    /**
     * Single markdown file with optional frontmatter YAML config.
     */
    public const CONTENT_STRATEGY_MARKDOWN_ONLY = 'markdown_only';

    /**
     * Only twig files.
     */
    public const CONTENT_STRATEGY_TWIG_ONLY = 'twig_only';

    /**
     * YAML config file and markdown content file.
     */
    public const CONTENT_STRATEGY_YAML_AND_MARKDOWN = 'yaml_and_markdown';

    /**
     * @var File[]
     */
    private ?array $files = null;

    /**
     * @var Directory[]
     */
    private ?array $directories = null;

    public function __construct(
        private SplFileInfo $directoryInfo,
        private readonly FileFactory $fileFactory,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?string $parentPath = null
    ) {
        $this->parseFiles();
        $this->parseDirectories();
    }

    /**
     * Parse this directory for files.
     */
    private function parseFiles(): void
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
     * Parse this directory for subdirectories.
     */
    private function parseDirectories(): void
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
    public function getFilesByType(string $type): array
    {
        return array_filter($this->files, static fn (File $file): bool => $file->getType() === $type);
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
    public function validateFiles(): void
    {
        $this->validateFileCounts();
        $this->validateBasenames();
    }

    /**
     * Validate number of files of specific types in this directory.
     */
    private function validateFileCounts(): void
    {
        $yamlFiles = $this->getFilesByType(FileTypes::TYPE_YAML);
        if (count($yamlFiles) > 1) {
            throw StructureException::moreThanOneYamlFile($this->directoryInfo, $yamlFiles);
        }
        $markdownFiles = $this->getFilesByType(FileTypes::TYPE_MARKDOWN);
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
        if (!$this->hasYamlFile()) {
            return true;
        }
        if (!$this->hasMarkdownFile()) {
            return true;
        }

        return $this->getYamlFile()?->getDefaultBasename() === $this->getMarkdownFile()?->getDefaultBasename();
    }

    public function getYamlFile(): ?File
    {
        $files = $this->getFilesByType(FileTypes::TYPE_YAML);

        return count($files) ? current($files) : null;
    }

    public function hasYamlFile(): bool
    {
        return $this->getYamlFile() instanceof File;
    }

    public function getMarkdownFile(): ?File
    {
        $files = $this->getFilesByType(FileTypes::TYPE_MARKDOWN);

        return count($files) ? current($files) : null;
    }

    public function hasMarkdownFile(): bool
    {
        return $this->getMarkdownFile() instanceof File;
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
        return $this->getFilesByType(FileTypes::TYPE_TWIG);
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
     * Subdirectories indexed by directory name.
     *
     * @return Directory[]
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Get files of this directory and all subdirectories.
     *
     * @return File[]
     */
    public function getFilesRecursively(): array
    {
        $files = $this->files;
        foreach ($this->directories as $directory) {
            foreach ($directory->getFilesRecursively() as $path => $file) {
                $files[$directory->getName().'/'.$path] = $file;
            }
        }

        return $files;
    }

    public function fetchPageContent(bool $convertMarkdown): ?string
    {
        if ($this->hasMarkdownFile()) {
            return trim($this->getFrontYAMLDocument($convertMarkdown)->getContent());
        }

        return null;
    }

    public function getFrontYAMLDocument(bool $convertMarkdown): Document
    {
        $markdownFile = $this->getMarkdownFile();
        if (!$markdownFile instanceof File) {
            throw FilesystemException::missingMarkdownFile($this);
        }

        return (new FrontYAMLParser())->parse(
            file_get_contents($markdownFile->getFilesystemPathname()),
            $convertMarkdown
        );
    }

    /**
     * Fetch page settings from either YAML or markdown/frontmatter.
     */
    public function fetchPageSettings(): array
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
}
