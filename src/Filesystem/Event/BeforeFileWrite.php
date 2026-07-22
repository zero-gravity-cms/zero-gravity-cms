<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Filesystem\Event;

final class BeforeFileWrite extends FileEvent
{
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
