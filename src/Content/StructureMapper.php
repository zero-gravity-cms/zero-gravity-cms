<?php

namespace ZeroGravity\Cms\Content;

interface StructureMapper
{
    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     */
    public function parse();

    public function getWritablePageInstance(ReadablePage $page): WritablePage;

    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage;

    /**
     * Store changes of the given page diff.
     */
    public function saveChanges(PageDiff $diff): void;
}
