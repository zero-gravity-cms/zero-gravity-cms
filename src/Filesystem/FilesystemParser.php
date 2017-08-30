<?php

namespace ZeroGravity\Cms\Filesystem;

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
     * FilesystemParser constructor.
     *
     * @param FileFactory $fileFactory
     * @param string      $path
     * @param bool        $convertMarkdown
     * @param array       $defaultPageSettings
     */
    public function __construct(
        FileFactory $fileFactory,
        string $path,
        bool $convertMarkdown,
        array $defaultPageSettings
    ) {
        $this->fileFactory = $fileFactory;
        $this->path = $path;
        $this->convertMarkdown = $convertMarkdown;
        $this->defaultPageSettings = $defaultPageSettings;
    }

    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     */
    public function parse()
    {
        if (!is_dir($this->path)) {
            throw FilesystemException::contentDirectoryDoesNotExist($this->path);
        }
        $directory = new ParsedDirectory(new SplFileInfo($this->path), $this->fileFactory);

        $pages = [];
        foreach ($directory->getDirectories() as $subDir) {
            $page = $subDir->createPage($this->convertMarkdown, $this->defaultPageSettings);

            if (null !== $page) {
                $pages[$page->getSlug()] = $page;
            }
        }

        return $pages;
    }
}
