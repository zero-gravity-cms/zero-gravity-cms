<?php

namespace ZeroGravity\Cms\Filesystem\Event;

class BeforeFileWrite extends FileEvent
{
    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
