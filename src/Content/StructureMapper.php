<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Exception\ZeroGravityException;

interface StructureMapper
{
    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     */
    public function parse(): array;

    public function getWritablePageInstance(ReadablePage $page): WritablePage;

    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage;

    /**
     * Store changes of the given page diff.
     *
     * @throws StructureException|ZeroGravityException
     */
    public function saveChanges(PageDiff $diff): void;
}
