<?php

namespace ZeroGravity\Cms\Content\Finder;

trait PageFinderPathsTrait
{
    private $paths = [];
    private $notPaths = [];
    private $filesystemPaths = [];
    private $notFilesystemPaths = [];

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
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function path($pattern)
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
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function notPath($pattern)
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
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function filesystemPath($pattern)
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
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function notFilesystemPath($pattern)
    {
        $this->notFilesystemPaths[] = $pattern;

        return $this;
    }

    private function applyPathsIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->paths) || !empty($this->notPaths)) {
            $iterator = new Iterator\PathFilterIterator($iterator, $this->paths, $this->notPaths);
        }

        return $iterator;
    }

    private function applyFilesystemPathsIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->filesystemPaths) || !empty($this->notFilesystemPaths)) {
            $iterator = new Iterator\FilesystemPathFilterIterator(
                $iterator,
                $this->filesystemPaths,
                $this->notFilesystemPaths
            );
        }

        return $iterator;
    }
}
