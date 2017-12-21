<?php

namespace ZeroGravity\Cms\Filesystem;

use Mni\FrontYAML\Parser as FrontYAMLParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Event\AfterCreatePage;
use ZeroGravity\Cms\Filesystem\Event\BeforeCreatePage;

class PageFactory
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface          $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create Page from directory content.
     *
     * @param Directory $directory
     * @param bool      $convertMarkdown
     * @param array     $defaultSettings
     * @param Page|null $parentPage
     *
     * @return null|Page
     */
    public function createPage(
        Directory $directory,
        bool $convertMarkdown,
        array $defaultSettings,
        Page $parentPage = null
    ) {
        $directory->validateFiles();
        if (!$directory->hasContentFiles()) {
            return null;
        }

        if (null !== $parentPage) {
            $defaultSettings = $this->mergeSettings($defaultSettings, $parentPage->getChildDefaults());
        }
        $settings = $this->buildPageSettings($defaultSettings, $directory, $parentPage);

        /* @var $event BeforeCreatePage */
        $event = $this->eventDispatcher->dispatch(
            BeforeCreatePage::BEFORE_CREATE_PAGE,
            new BeforeCreatePage($directory, $settings, $parentPage)
        );
        $page = new Page($directory->getName(), $event->getSettings(), $parentPage);
        $page->setContent($this->fetchPageContent($directory, $convertMarkdown));

        $files = $directory->getFiles();
        foreach ($directory->getDirectories() as $subDirectory) {
            $subPage = $this->createPage($subDirectory, $convertMarkdown, $defaultSettings, $page);
            if (null === $subPage) {
                foreach ($subDirectory->getFilesRecursively() as $path => $file) {
                    $files[$subDirectory->getName().'/'.$path] = $file;
                }
            }
        }
        $page->setFiles($files);
        $this->eventDispatcher->dispatch(
            AfterCreatePage::AFTER_CREATE_PAGE,
            new AfterCreatePage($page)
        );

        return $page;
    }

    /**
     * @param array     $defaultSettings
     * @param Directory $directory
     * @param Page      $parentPage
     *
     * @return array
     */
    private function buildPageSettings(array $defaultSettings, Directory $directory, Page $parentPage = null): array
    {
        $settings = $this->fetchPageSettings($directory);
        $defaultTemplate = $directory->getDefaultBasenameTwigFile();

        if (null !== $defaultTemplate && !isset($settings['content_template'])) {
            $parentPath = $parentPage ? $parentPage->getFilesystemPath() : '';
            $settings['content_template'] = sprintf('@ZeroGravity%s%s',
                $parentPath,
                $defaultTemplate->getPathname()
            );
        }

        $settings = $this->mergeSettings([
            'slug' => $directory->getSlug(),
            'visible' => $directory->hasSortingPrefix() && !$directory->hasUnderscorePrefix(),
            'module' => $directory->hasUnderscorePrefix(),
        ],
            $defaultSettings,
            $settings
        );

        return $settings;
    }

    /**
     * Fetch page settings from either YAML or markdown/frontmatter.
     *
     * @param Directory $directory
     *
     * @return array
     */
    private function fetchPageSettings(Directory $directory)
    {
        if ($directory->hasYamlFile()) {
            $data = Yaml::parse(file_get_contents($directory->getYamlFile()->getFilesystemPathname()));

            return is_array($data) ? $data : [];
        } elseif ($directory->hasMarkdownFile()) {
            return $this->getFrontYAMLDocument($directory, false)->getYAML() ?: [];
        }

        return [];
    }

    /**
     * @param Directory $directory
     * @param bool      $convertMarkdown
     *
     * @return null|string
     */
    private function fetchPageContent(Directory $directory, bool $convertMarkdown)
    {
        if ($directory->hasMarkdownFile()) {
            return trim($this->getFrontYAMLDocument($directory, $convertMarkdown)->getContent());
        }

        return '';
    }

    /**
     * Merge 2 or more arrays, deep merging array values while replacing scalar values.
     *
     * @param array[] $params 1 or more arrays to merge
     *
     * @return array
     */
    private function mergeSettings(array ...$params): array
    {
        $settings = [];
        foreach ($params as $param) {
            foreach ($param as $key => $value) {
                if (isset($settings[$key]) && is_array($settings[$key]) && is_array($value)) {
                    $settings[$key] = $this->mergeSettings($settings[$key], $value);
                } else {
                    $settings[$key] = $value;
                }
            }
        }

        return $settings;
    }

    /**
     * @param Directory $directory
     * @param bool      $convertMarkdown
     *
     * @return \Mni\FrontYAML\Document
     */
    private function getFrontYAMLDocument(Directory $directory, bool $convertMarkdown): \Mni\FrontYAML\Document
    {
        $parser = new FrontYAMLParser();
        $document = $parser->parse(
            file_get_contents($directory->getMarkdownFile()->getFilesystemPathname()),
            $convertMarkdown
        );

        return $document;
    }
}
