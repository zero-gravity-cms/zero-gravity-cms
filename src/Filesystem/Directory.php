<?php

namespace ZeroGravity\Cms\Filesystem;

use Mni\FrontYAML\Parser as FrontYAMLParser;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SplFileInfo     $directoryInfo
     * @param FileFactory     $fileFactory
     * @param LoggerInterface $logger
     * @param string|null     $parentPath
     */
    public function __construct(
        SplFileInfo $directoryInfo,
        FileFactory $fileFactory,
        LoggerInterface $logger,
        string $parentPath = null
    ) {
        $this->directoryInfo = $directoryInfo;
        $this->fileFactory = $fileFactory;
        $this->parentPath = $parentPath;
        $this->logger = $logger;
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
                $this->logger,
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

        $settings = $this->buildPageSettings($groupedFiles, $defaultPageSettings, $parentPage);

        $page = new Page($this->getName(), $settings, $parentPage);
        $page->setContent($this->fetchPageContent($groupedFiles, $convertMarkdown));

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
     * @param array $groupedFiles
     * @param array $defaultPageSettings
     * @param Page  $parentPage
     *
     * @return array
     */
    private function buildPageSettings(array $groupedFiles, array $defaultPageSettings, Page $parentPage = null): array
    {
        $settings = $this->fetchPageSettings($groupedFiles);
        if (isset($groupedFiles['default_twig']) && !isset($settings['content_template'])) {
            $parentPath = $parentPage ? $parentPage->getFilesystemPath() : '';
            $settings['content_template'] = sprintf('@ZeroGravity%s%s',
                $parentPath,
                $groupedFiles['default_twig']->getPathname()
            );
        }

        $settings = $this->mergeSettings([
                'slug' => $this->getSlug(),
                'visible' => $this->hasSortingPrefix() && !$this->hasUnderscorePrefix(),
                'module' => $this->hasUnderscorePrefix(),
            ],
            $defaultPageSettings,
            $parentPage ? $parentPage->getChildDefaults() : [],
            $settings
        );

        return $settings;
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

            return trim($document->getContent());
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
        return array_filter($this->getFiles(), function (File $file) use ($type) {
            return $file->getType() === $type;
        });
    }

    /**
     * Get files contained in this directory, grouped by type.
     *
     * @return File[]
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
        if (count($twigFiles) > 0) {
            $files['twig'] = $twigFiles;
        }
        if (1 == count($twigFiles)) {
            $twigFile = current($twigFiles);
            $twigBase = $twigFile->getBasename('.html.'.$twigFile->getExtension());

            foreach (['yaml', 'markdown'] as $checkBase) {
                if (isset($files[$checkBase]) && $files[$checkBase]->getDefaultBasename() === $twigBase) {
                    $files['default_twig'] = $twigFile;
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

    private function mergeSettings(): array
    {
        $settings = [];
        foreach (func_get_args() as $array) {
            foreach ($array as $key => $value) {
                if (isset($settings[$key]) && is_array($settings[$key]) && is_array($value)) {
                    $settings[$key] = $this->mergeSettings($settings[$key], $value);
                } else {
                    $settings[$key] = $value;
                }
            }
        }

        return $settings;
    }
}
