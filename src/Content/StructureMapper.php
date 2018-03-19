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

    /**
     * @param ReadablePage $page
     *
     * @return WritablePage
     */
    public function getWritablePageInstance(ReadablePage $page): WritablePage;

    /**
     * @return WritablePage
     */
    public function getNewWritablePage(ReadablePage $parentPage = null): WritablePage;

    /**
     * Store changes of the given page diff.
     *
     * @param PageDiff $diff
     */
    public function saveChanges(PageDiff $diff): void;
}
