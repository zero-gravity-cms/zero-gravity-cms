<?php

namespace ZeroGravity\Cms\Content;

interface StructureParser
{
    /**
     * Parse any content source for all Page data and return Page tree as array containing base nodes.
     *
     * @return Page[]
     */
    public function parse();
}
