<?php

namespace ZeroGravity\Cms\Filesystem;

use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
        if ($diff->contentHasChanged()) {
            $directory->saveContent($diff->getNewContentRaw());
        }
        if ($diff->settingsHaveChanged()) {
            $directory->saveSettings($diff->getNewSettings());
        }
        if ($diff->nameHasChanged()) {
            $directory->changeName($diff->getNewName());
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
        if ($diff->nameHasChanged() && $this->newNameAlreadyExists($diff)) {
            $this->logAndThrow(StructureException::newPageNameAlreadyExists($diff));
        }
    }

    /**
     * @param PageDiff $diff
     *
     * @return bool
     */
    private function newNameAlreadyExists(PageDiff $diff): bool
    {
        $parentPath = dirname($diff->getOld()->getDirectory()->getFilesystemPathname());

        return is_dir($parentPath.'/'.$diff->getNewName());
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
}
