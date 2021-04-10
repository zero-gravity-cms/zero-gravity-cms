<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;

class AfterPageCreate extends Event
{
    private Page $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
