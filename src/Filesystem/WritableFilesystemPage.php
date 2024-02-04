<?php

namespace ZeroGravity\Cms\Filesystem;

use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Content\WritablePage;

final class WritableFilesystemPage extends Page implements WritablePage
{
    use BasicWritablePageTrait;

    public function __construct(
        ReadablePage $page,
        private readonly ?Directory $directory = null,
    ) {
        if ($this->directory instanceof Directory) {
            $this->contentRaw = $this->directory->fetchPageContent(false);
        }

        parent::__construct($page->getName(), $page->getSettings(), $page->getParent());
    }

    public function getDirectory(): ?Directory
    {
        return $this->directory;
    }
}
