<?php

namespace ZeroGravity\Cms\Filesystem;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\Event\AfterPageCreate;
use ZeroGravity\Cms\Filesystem\Event\BeforePageCreate;

/**
 * @phpstan-import-type SettingValue from PageSettings
 */
final class PageFactory
{
    /**
     * @var array<string, Directory>
     */
    private array $directories = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Create Page from directory content.
     *
     * @param array<string, SettingValue> $defaultSettings
     */
    public function createPage(
        Directory $directory,
        bool $convertMarkdown,
        array $defaultSettings,
        Page $parentPage = null
    ): ?Page {
        $directory->validateFiles();
        if (Directory::CONTENT_STRATEGY_NONE === $directory->getContentStrategy()) {
            return null;
        }

        if ($parentPage instanceof Page) {
            $defaultSettings = $this->mergeSettings($defaultSettings, $parentPage->getChildDefaults());
        }
        $settings = $this->buildPageSettings($defaultSettings, $directory, $parentPage);

        /* @var $handledBeforeCreatePage BeforePageCreate */
        $handledBeforeCreatePage = $this->eventDispatcher->dispatch(
            new BeforePageCreate($directory, $settings, $parentPage)
        );
        $page = new Page($directory->getName(), $handledBeforeCreatePage->getSettings(), $parentPage);
        $page->setContent($directory->fetchPageContent($convertMarkdown));

        $files = $directory->getFiles();
        foreach ($directory->getDirectories() as $subDirectory) {
            $subPage = $this->createPage($subDirectory, $convertMarkdown, $defaultSettings, $page);
            if (!$subPage instanceof Page) {
                foreach ($subDirectory->getFilesRecursively() as $path => $file) {
                    $files[$subDirectory->getName().'/'.$path] = $file;
                }
            }
        }
        $page->setFiles($files);
        $this->eventDispatcher->dispatch(new AfterPageCreate($page));
        $this->directories[$page->getPath()->toString()] = $directory;

        $this->logger->debug("Created page '{$page->getTitle()}' ({$page->getPath()})", [
            'parent' => $parentPage?->getPath(),
            'settings' => $settings,
        ]);

        return $page;
    }

    /**
     * @param array<string, SettingValue> $defaultSettings
     *
     * @return array<string, SettingValue>
     */
    private function buildPageSettings(array $defaultSettings, Directory $directory, Page $parentPage = null): array
    {
        $settings = $directory->fetchPageSettings();
        $defaultTemplate = $directory->getDefaultBasenameTwigFile();

        if ($defaultTemplate instanceof File && !isset($settings['content_template'])) {
            $parentPath = $parentPage instanceof Page ? rtrim($parentPage->getFilesystemPath(), '/') : '';
            $settings['content_template'] = sprintf('@ZeroGravity%s/%s/%s',
                $parentPath,
                $directory->getName(),
                $defaultTemplate->getFilename()
            );
        }

        return $this->mergeSettings(
            [
                'slug' => $directory->getSlug(),
                'visible' => $directory->hasSortingPrefix() && !$directory->hasUnderscorePrefix(),
                'module' => $directory->hasUnderscorePrefix(),
            ],
            $defaultSettings,
            $settings
        );
    }

    /**
     * Merge 2 or more arrays, deep merging array values while replacing scalar values.
     *
     * @param array<string, SettingValue> $params 1 or more arrays to merge
     *
     * @return array<string, SettingValue>
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
     * Get the directory for a previously created page instance.
     */
    public function getDirectory(ReadablePage $page): Directory
    {
        if (!isset($this->directories[$page->getPath()->toString()])) {
            throw FilesystemException::cannotFindDirectoryForPage($page);
        }

        return $this->directories[$page->getPath()->toString()];
    }
}
