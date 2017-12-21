<?php

namespace ZeroGravity\Cms\Filesystem;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
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
        $yamlFiles = $this->getFilesByType(FileTypeDetector::TYPE_YAML);
        if (count($yamlFiles) > 1) {
            throw StructureException::moreThanOneYamlFile($this->directoryInfo, $yamlFiles);
        }
        $markdownFiles = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);
        if (count($markdownFiles) > 1) {
            throw StructureException::moreThanOneMarkdownFile($this->directoryInfo, $markdownFiles);
        }

        if (
            $this->hasYamlFile() && $this->hasMarkdownFile()
            && ($this->getYamlFile()->getDefaultBasename() !== $this->getMarkdownFile()->getDefaultBasename())
        ) {
            throw StructureException::yamlAndMarkdownFilesMismatch(
                $this->directoryInfo,
                $this->getYamlFile(),
                $this->getMarkdownFile()
            );
        }
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
     * @return bool
     */
    public function hasContentFiles(): bool
    {
        return $this->hasMarkdownFile() || $this->hasYamlFile() || count($this->getTwigFiles()) > 0;
    }

    /**
     * Parse this directory for files.
     */
    private function parseFiles()
    {
        if (null !== $this->files) {
            return;
        }

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
        if (null !== $this->directories) {
            return;
        }

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
     * File objects indexed by filename.
     *
     * @return File[]
     */
    public function getFiles(): array
    {
        $this->parseFiles();

        return $this->files;
    }

    /**
     * Sub directories indexed by directory name.
     *
     * @return Directory[]
     */
    public function getDirectories(): array
    {
        $this->parseDirectories();

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
}
