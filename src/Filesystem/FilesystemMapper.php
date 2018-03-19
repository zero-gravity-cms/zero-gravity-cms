<?php

namespace ZeroGravity\Cms\Filesystem;

use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Content\StructureMapper;
use ZeroGravity\Cms\Content\WritablePage;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Exception\ZeroGravityException;

class FilesystemMapper implements StructureMapper
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $convertMarkdown;

    /**
     * @var array
     */
    private $defaultPageSettings;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Directory[]
     */
    private $directories;

    /**
     * FilesystemMapper constructor.
     *
     * @param FileFactory              $fileFactory
     * @param string                   $path
     * @param bool                     $convertMarkdown
     * @param array                    $defaultPageSettings
     * @param LoggerInterface          $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FileFactory $fileFactory,
        string $path,
        bool $convertMarkdown,
        array $defaultPageSettings,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->fileFactory = $fileFactory;
        $this->path = $path;
        $this->convertMarkdown = $convertMarkdown;
        $this->defaultPageSettings = $defaultPageSettings;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     *
     * @throws ZeroGravityException|FilesystemException
     */
    public function parse()
    {
        $this->checkPath();

        $this->logger->info('Parsing filesystem for page content, starting at {path}', ['path' => $this->path]);
        $directory = new Directory(new SplFileInfo($this->path), $this->fileFactory);

        $pageFactory = new PageFactory($this->logger, $this->eventDispatcher);
        $pages = [];
        $this->directories = [];
        foreach ($directory->getDirectories() as $subDir) {
            $page = $pageFactory->createPage($subDir, $this->convertMarkdown, $this->defaultPageSettings);

            if (null !== $page) {
                $pages[$page->getPath()->toString()] = $page;
                $this->directories[$page->getPath()->toString()] = $subDir;
            }
        }

        return $pages;
    }

    /**
     * Throw an exception if the content path does not exist.
     *
     * @throws FilesystemException|ZeroGravityException
     */
    private function checkPath()
    {
        if (!is_dir($this->path)) {
            $this->logAndThrow(FilesystemException::contentDirectoryDoesNotExist($this->path));
        }
    }

    /**
     * @param ReadablePage $page
     *
     * @return WritableFilesystemPage
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage
    {
        return new WritableFilesystemPage($page, $this->directories[$page->getPath()->toString()]);
    }

    /**
     * @param ReadablePage|null $parentPage
     *
     * @return WritableFilesystemPage
     */
    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage
    {
        return new WritableFilesystemPage(new Page('', [], $parentPage));
    }

    /**
     * Store changes of the given page diff.
     *
     * @param PageDiff $diff
     *
     * @throws FilesystemException|StructureException|ZeroGravityException
     */
    public function saveChanges(PageDiff $diff): void
    {
        $this->validateDiff($diff);

        /* @var $directory Directory */
        $directory = $diff->getNew()->getDirectory();
        $isNew = false;
        if (null === $directory) {
            $isNew = true;
            $directory = $this->createDirectoryForNewPage($diff);
        }

        if ($diff->contentHasChanged()) {
            $directory->saveContent($diff->getNewContentRaw());
        }
        if ($diff->settingsHaveChanged()) {
            $directory->saveSettings($this->getNonDefaultSettingsForDiff($diff));
        }
        if (!$isNew && $diff->filesystemPathHasChanged()) {
            $directory->renameOrMove($this->path.$diff->getNewFilesystemPath());
        }
    }

    /**
     * @param PageDiff $diff
     *
     * @throws ZeroGravityException
     */
    private function validateDiff(PageDiff $diff): void
    {
        if (!$diff->containsInstancesOf(WritableFilesystemPage::class)) {
            $this->logAndThrow(FilesystemException::unsupportedWritablePageClass($diff));
        }
        if ($diff->filesystemPathHasChanged() && $this->newFilesystemPathAlreadyExists($diff)) {
            $this->logAndThrow(StructureException::newPageNameAlreadyExists($diff));
        }
    }

    /**
     * @param PageDiff $diff
     *
     * @return bool
     */
    private function newFilesystemPathAlreadyExists(PageDiff $diff): bool
    {
        return is_dir($this->path.$diff->getNew()->getFilesystemPath()->toString());
    }

    /**
     * @param ZeroGravityException $exception
     *
     * @throws ZeroGravityException
     */
    private function logAndThrow(ZeroGravityException $exception)
    {
        $this->logger->error($exception->getMessage());

        throw $exception;
    }

    /**
     * @param PageDiff $diff
     *
     * @return Directory
     */
    private function createDirectoryForNewPage(PageDiff $diff): Directory
    {
        $realPath = $this->path.$diff->getNewFilesystemPath();
        $parentPath = $diff->getNew()->getParent() ? $diff->getNew()->getParent()->getFilesystemPath() : '';

        $fs = new Filesystem();
        $fs->mkdir($realPath);
        $directory = new Directory(new SplFileInfo($realPath), $this->fileFactory, $parentPath);

        return $directory;
    }

    /**
     * @param PageDiff $diff
     *
     * @return array
     */
    private function getNonDefaultSettingsForDiff(PageDiff $diff): array
    {
        $settings = $diff->getNewNonDefaultSettings();
        if (null !== $diff->getNew()->getParent()) {
            return $settings;
        }

        foreach ($this->defaultPageSettings as $key => $defaultValue) {
            if (array_key_exists($key, $settings) && $settings[$key] === $defaultValue) {
                unset($settings[$key]);
            }
        }

        return $settings;
    }
}
