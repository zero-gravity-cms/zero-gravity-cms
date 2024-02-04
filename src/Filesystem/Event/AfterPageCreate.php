<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;

final class AfterPageCreate extends Event
{
    public function __construct(
        private readonly Page $page
    ) {
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
