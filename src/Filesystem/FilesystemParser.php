<?php

namespace ZeroGravity\Cms\Filesystem;

use Psr\Log\LoggerInterface;
use SplFileInfo;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\StructureParser;
use ZeroGravity\Cms\Exception\FilesystemException;

class FilesystemParser implements StructureParser
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
     * FilesystemParser constructor.
     *
     * @param FileFactory     $fileFactory
     * @param string          $path
     * @param bool            $convertMarkdown
     * @param array           $defaultPageSettings
     * @param LoggerInterface $logger
     */
    public function __construct(
        FileFactory $fileFactory,
        string $path,
        bool $convertMarkdown,
        array $defaultPageSettings,
        LoggerInterface $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->path = $path;
        $this->convertMarkdown = $convertMarkdown;
        $this->defaultPageSettings = $defaultPageSettings;
        $this->logger = $logger;
    }

    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     */
    public function parse()
    {
        if (!is_dir($this->path)) {
            $this->logger->error('Cannot parse filesystem: page content directory {path} does not exist', ['path' => $this->path]);
            throw FilesystemException::contentDirectoryDoesNotExist($this->path);
        }

        $this->logger->info('Parsing filesystem for page content, starting at {path}', ['path' => $this->path]);
        $directory = new Directory(new SplFileInfo($this->path), $this->fileFactory, $this->logger);

        $pages = [];
        foreach ($directory->getDirectories() as $subDir) {
            $page = $subDir->createPage($this->convertMarkdown, $this->defaultPageSettings);

            if (null !== $page) {
                $pages[$page->getPath()->toString()] = $page;
            }
        }

        return $pages;
    }
}
