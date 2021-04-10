<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Exception\ZeroGravityException;

interface WritablePageRepository
{
    /**
     * Get writable instance of an existing page.
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage;

    /**
     * Get new writable page instance.
     */
    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage;

    /**
     * Store changes of the given page diff.
     *
     * @throws StructureException|ZeroGravityException
     */
    public function saveChanges(PageDiff $diff): void;
}
