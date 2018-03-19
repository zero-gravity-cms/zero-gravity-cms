<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Component\EventDispatcher\Event;
use ZeroGravity\Cms\Content\PageDiff;

abstract class PageDiffEvent extends Event
{
    /**
     * @var PageDiff
     */
    private $diff;

    public function __construct(PageDiff $diff)
    {
        $this->diff = $diff;
    }

    /**
     * @return PageDiff
     */
    public function getDiff(): PageDiff
    {
        return $this->diff;
    }
}
