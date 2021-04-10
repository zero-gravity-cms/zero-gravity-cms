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
use ZeroGravity\Cms\Filesystem\Event\AfterPageSave;
use ZeroGravity\Cms\Filesystem\Event\BeforePageSave;
use ZeroGravity\Cms\Filesystem\Event\BeforePageSaveValidate;

class FilesystemMapper implements StructureMapper
{
    private FileFactory $fileFactory;

    private string $path;

    private bool $convertMarkdown;

    private array $defaultPageSettings;

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private PageFactory $pageFactory;

    /**
     * FilesystemMapper constructor.
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

        $this->pageFactory = new PageFactory($this->logger, $this->eventDispatcher);
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
        $directory = $this->createDirectory($this->path);

        $pages = [];
        foreach ($directory->getDirectories() as $subDir) {
            $page = $this->pageFactory->createPage($subDir, $this->convertMarkdown, $this->defaultPageSettings);

            if (null !== $page) {
                $pages[$page->getPath()->toString()] = $page;
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
     * @return WritableFilesystemPage
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage
    {
        return new WritableFilesystemPage($page, $this->pageFactory->getDirectory($page));
    }

    /**
     * @return WritableFilesystemPage
     */
    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage
    {
        return new WritableFilesystemPage(new Page('', [], $parentPage));
    }

    /**
     * Store changes of the given page diff.
     *
     * @throws FilesystemException|StructureException|ZeroGravityException
     */
    public function saveChanges(PageDiff $diff): void
    {
        $this->eventDispatcher->dispatch(new BeforePageSaveValidate($diff));

        $this->validateDiff($diff);

        $this->eventDispatcher->dispatch(new BeforePageSave($diff));

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

        $this->eventDispatcher->dispatch(new AfterPageSave($diff));
    }

    /**
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

    private function newFilesystemPathAlreadyExists(PageDiff $diff): bool
    {
        return is_dir($this->path.$diff->getNew()->getFilesystemPath()->toString());
    }

    /**
     * @throws ZeroGravityException
     */
    private function logAndThrow(ZeroGravityException $exception)
    {
        $this->logger->error($exception->getMessage());

        throw $exception;
    }

    private function createDirectoryForNewPage(PageDiff $diff): Directory
    {
        $realPath = $this->path.$diff->getNewFilesystemPath();
        $parentPath = $diff->getNew()->getParent() ? $diff->getNew()->getParent()->getFilesystemPath() : '';

        $fs = new Filesystem();
        $fs->mkdir($realPath);
        $directory = $this->createDirectory($realPath, $parentPath);

        return $directory;
    }

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

    private function createDirectory(string $path, string $parentPath = null): Directory
    {
        $directory = new Directory(
            new SplFileInfo($path),
            $this->fileFactory,
            $this->logger,
            $this->eventDispatcher,
            $parentPath
        );

        return $directory;
    }
}
