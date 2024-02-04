<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\PageDiff;

abstract class PageDiffEvent extends Event
{
    public function __construct(
        private readonly PageDiff $diff,
    ) {
    }

    public function getDiff(): PageDiff
    {
        return $this->diff;
    }
}
