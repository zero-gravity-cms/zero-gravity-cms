<?php

namespace ZeroGravity\Cms\Filesystem;

use Mni\FrontYAML\Parser as FrontYAMLParser;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\StructureException;

class ParsedDirectory
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
    private $files = [];

    /**
     * @var ParsedDirectory[]
     */
    private $directories = [];

    public function __construct(SplFileInfo $directoryInfo, FileFactory $fileFactory, string $parentPath = null)
    {
        $this->directoryInfo = $directoryInfo;
        $this->fileFactory = $fileFactory;
        $this->parentPath = $parentPath;

        $this->init();
    }

    public function getPath(): string
    {
        if (null === $this->parentPath) {
            // don't include name of top level directory in the path
            return '';
        }

        return $this->parentPath.'/'.$this->getName();
    }

    private function init()
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
        foreach ($fileFinder as $fileInfo) {
            /* @var $fileInfo FinderSplFileInfo */
            $filePath = $this->getPath().'/'.$fileInfo->getRelativePathname();
            $this->files[$fileInfo->getFilename()] = $this->fileFactory->createFile($filePath);
        }

        $subDirectoryFinder = Finder::create()
            ->directories()
            ->depth(0)
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->sortByName()
            ->in($this->directoryInfo->getRealPath())
        ;
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
        return $this->files;
    }

    /**
     * Sub directories indexed by directory name.
     *
     * @return ParsedDirectory[]
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
     * Create Page from directory content.
     *
     * @param bool      $convertMarkdown
     * @param array     $defaultPageSettings
     * @param Page|null $parentPage
     *
     * @return null|Page
     */
    public function createPage(bool $convertMarkdown, array $defaultPageSettings, Page $parentPage = null)
    {
        $groupedFiles = $this->groupAndValidateFiles();

        if (0 === count($groupedFiles)) {
            return null;
        }

        $content = $this->fetchPageContent($groupedFiles, $convertMarkdown);
        $settings = array_merge([
                'slug' => $this->getSlug(),
                'is_visible' => $this->hasSortingPrefix(),
                'is_modular' => $this->hasUnderscorePrefix(),
            ],
            $defaultPageSettings,
            $this->fetchPageSettings($groupedFiles)
        );

        $page = new Page($this->getName(), $settings, $parentPage);
        $page->validateSettings();
        $page->setContent($content);

        $files = $this->getFiles();
        foreach ($this->getDirectories() as $directory) {
            $subPage = $directory->createPage($convertMarkdown, $defaultPageSettings, $page);
            if (null === $subPage) {
                foreach ($directory->getFilesRecursively() as $path => $file) {
                    $files[$directory->getName().'/'.$path] = $file;
                }
            }
        }
        $page->setFiles($files);

        return $page;
    }

    /**
     * Fetch page settings from either YAML or markdown/frontmatter.
     *
     * @param File[] $files
     *
     * @return array
     */
    private function fetchPageSettings(array $files)
    {
        if (isset($files['yaml'])) {
            $data = Yaml::parse(file_get_contents($files['yaml']->getFilesystemPathname()));

            return is_array($data) ? $data : [];
        } elseif (isset($files['markdown'])) {
            $parser = new FrontYAMLParser();
            $document = $parser->parse(file_get_contents($files['markdown']->getFilesystemPathname()), false);

            return $document->getYAML() ?: [];
        }

        return [];
    }

    /**
     * @param array $files
     * @param bool  $convertMarkdown
     *
     * @return null|string
     */
    private function fetchPageContent(array $files, bool $convertMarkdown)
    {
        if (isset($files['markdown'])) {
            $parser = new FrontYAMLParser();
            $document = $parser->parse(file_get_contents($files['markdown']->getFilesystemPathname()), $convertMarkdown);

            return $document->getContent();
        }

        return '';
    }

    /**
     * @param string $type
     *
     * @return File[]
     */
    private function getFilesByType(string $type)
    {
        return array_filter($this->files, function (File $file) use ($type) {
            return $file->getType() === $type;
        });
    }

    /**
     * Get files contained in this directory, grouped by type.
     *
     * @return array
     */
    private function groupAndValidateFiles(): array
    {
        $yamlFiles = $this->getFilesByType(FileTypeDetector::TYPE_YAML);
        $markdownFiles = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);
        $twigFiles = $this->getFilesByType(FileTypeDetector::TYPE_TWIG);

        if (count($yamlFiles) > 1) {
            throw StructureException::moreThanOneYamlFile($this->directoryInfo, $yamlFiles);
        }
        if (count($markdownFiles) > 1) {
            throw StructureException::moreThanOneMarkdownFile($this->directoryInfo, $markdownFiles);
        }
        if (count($twigFiles) > 1) {
            throw StructureException::moreThanOneTwigFile($this->directoryInfo, $twigFiles);
        }

        $files = [];
        if (1 == count($yamlFiles)) {
            $files['yaml'] = current($yamlFiles);
            $files['base'] = $files['yaml']->getDefaultBasename();
        }
        if (1 == count($markdownFiles)) {
            $files['markdown'] = current($markdownFiles);
            $files['base'] = $files['markdown']->getDefaultBasename();

            if (isset($files['yaml'])) {
                $markdownBase = $files['markdown']->getDefaultBasename();
                $yamlBase = $files['yaml']->getDefaultBasename();

                if ($yamlBase !== $markdownBase) {
                    throw StructureException::yamlAndMarkdownFilesMismatch(
                        $this->directoryInfo,
                        $files['yaml'],
                        $files['markdown']
                    );
                }
            }
        }
        if (1 == count($twigFiles)) {
            $files['twig'] = current($twigFiles);
            $files['base'] = $files['twig']->getBasename('.html.'.$files['twig']->getExtension());

            if (isset($files['yaml'])) {
                $twigBase = $files['twig']->getBasename('.html.'.$files['twig']->getExtension());
                $yamlBase = $files['yaml']->getDefaultBasename();

                if ($yamlBase !== $twigBase) {
                    throw StructureException::yamlAndTwigFilesMismatch(
                        $this->directoryInfo,
                        $files['yaml'],
                        $files['twig']
                    );
                }
            }
        }

        return $files;
    }

    /**
     * @return bool
     */
    private function hasSortingPrefix(): bool
    {
        return (bool) preg_match(self::SORTING_PREFIX_PATTERN, $this->getName());
    }

    /**
     * @return bool
     */
    private function hasUnderscorePrefix(): bool
    {
        return (bool) preg_match(self::MODULAR_PREFIX_PATTERN, $this->getName());
    }
}
