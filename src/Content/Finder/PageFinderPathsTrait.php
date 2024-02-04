<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\FilesystemPathFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\PathFilterIterator;
use ZeroGravity\Cms\Content\ReadablePage;

trait PageFinderPathsTrait
{
    /**
     * @var list<string>
     */
    private array $paths = [];
    /**
     * @var list<string>
     */
    private array $notPaths = [];
    /**
     * @var list<string>
     */
    private array $filesystemPaths = [];
    /**
     * @var list<string>
     */
    private array $notFilesystemPaths = [];

    /**
     * Adds rules that page paths must match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->path('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @see FilenameFilterIterator
     */
    public function path(string $pattern): self
    {
        $this->paths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page paths must not match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->notPath('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @see FilenameFilterIterator
     */
    public function notPath(string $pattern): self
    {
        $this->notPaths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page filesystem paths must match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->path('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @see FilenameFilterIterator
     */
    public function filesystemPath(string $pattern): self
    {
        $this->filesystemPaths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page filesystem paths must not match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->notPath('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @see FilenameFilterIterator
     */
    public function notFilesystemPath(string $pattern): self
    {
        $this->notFilesystemPaths[] = $pattern;

        return $this;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyPathsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->paths) || !empty($this->notPaths)) {
            return new PathFilterIterator($iterator, $this->paths, $this->notPaths);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyFilesystemPathsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->filesystemPaths) || !empty($this->notFilesystemPaths)) {
            return new FilesystemPathFilterIterator(
                $iterator,
                $this->filesystemPaths,
                $this->notFilesystemPaths
            );
        }

        return $iterator;
    }
}
