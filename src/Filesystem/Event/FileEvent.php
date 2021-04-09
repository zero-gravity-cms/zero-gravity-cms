<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Filesystem\Directory;

abstract class FileEvent extends Event
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    private $realPath;

    /**
     * @var Directory
     */
    private $directory;

    public function __construct(string $realPath, string $content, Directory $directory)
    {
        $this->realPath = $realPath;
        $this->content = $content;
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getRealPath(): string
    {
        return $this->realPath;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return Directory
     */
    public function getDirectory(): Directory
    {
        return $this->directory;
    }
}
