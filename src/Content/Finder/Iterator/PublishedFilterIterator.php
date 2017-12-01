<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ZeroGravity\Cms\Content\Page;

/**
 * PublishedFilterIterator filters out pages that do not match the required published state.
 *
 * @method Page current()
 */
class PublishedFilterIterator extends \FilterIterator
{
    /**
     * @var bool
     */
    private $published;

    /**
     * @param \Iterator $iterator  The Iterator to filter
     * @param bool      $published
     */
    public function __construct(\Iterator $iterator, bool $published)
    {
        $this->published = $published;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->current()->isPublished() === $this->published;
    }
}
