<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Symfony\Component\Finder\Iterator\FilterIterator;
use ZeroGravity\Cms\Content\Page;

/**
 * VisibleFilterIterator filters out pages that do not match the required visible state.
 *
 * @method Page current()
 */
class VisibleFilterIterator extends FilterIterator
{
    /**
     * @var bool
     */
    private $visible;

    /**
     * @param \Iterator $iterator The Iterator to filter
     * @param bool      $visible
     */
    public function __construct(\Iterator $iterator, bool $visible)
    {
        $this->visible = $visible;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->current()->isVisible() === $this->visible;
    }
}
