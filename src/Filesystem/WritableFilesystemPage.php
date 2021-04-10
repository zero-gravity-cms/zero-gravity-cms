<?php

namespace ZeroGravity\Cms\Filesystem;

use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Content\WritablePage;

class WritableFilesystemPage extends Page implements WritablePage
{
    use BasicWritablePageTrait;

    private ?Directory $directory = null;

    public function __construct(ReadablePage $page, Directory $directory = null)
    {
        $this->directory = $directory;
        if (null !== $directory) {
            $this->contentRaw = $directory->fetchPageContent(false);
        }

        parent::__construct($page->getName(), $page->getSettings(), $page->getParent());
    }

    /**
     * @return Directory
     */
    public function getDirectory(): ? Directory
    {
        return $this->directory;
    }
}
