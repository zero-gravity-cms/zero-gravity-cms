<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Filesystem\Directory;

abstract class FileEvent extends Event
{
    public function __construct(
        private readonly string $realPath,
        protected string $content,
        private readonly Directory $directory,
    ) {
    }

    public function getRealPath(): string
    {
        return $this->realPath;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getDirectory(): Directory
    {
        return $this->directory;
    }
}
