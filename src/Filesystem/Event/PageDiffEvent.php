<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
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

    public function getDiff(): PageDiff
    {
        return $this->diff;
    }
}
