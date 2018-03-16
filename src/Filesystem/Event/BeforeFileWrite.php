<?php

namespace ZeroGravity\Cms\Filesystem\Event;

class BeforeFileWrite extends FileEvent
{
    public const BEFORE_FILE_WRITE = 'zerogravity.filesystem.before_file_write';

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
