<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Filesystem\Directory;

abstract class FileEvent extends Event
{
    protected string $content;

    private string $realPath;

    private Directory $directory;

    public function __construct(string $realPath, string $content, Directory $directory)
    {
        $this->realPath = $realPath;
        $this->content = $content;
        $this->directory = $directory;
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
