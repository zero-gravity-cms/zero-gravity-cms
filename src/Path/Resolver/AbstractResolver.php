<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Finder\Glob;
use ZeroGravity\Cms\Path\Path;

abstract class AbstractResolver implements SinglePathResolver
{
    /**
     * Try moving pattern parts into parent path because globs don't work with paths.
     *
     * @param Path|null $parentPath
     */
    protected function moveNonGlobsToParent(Path $path, Path $parentPath): void
    {
        $fromElements = $path->getElements();
        while (count($fromElements) > 1 && !$fromElements[0]->isGlob()) {
            $parentPath->appendElement(array_shift($fromElements));
        }

        $path->setElements($fromElements);
    }

    /**
     * Converts strings to regular expression with starting match.
     * Glob patterns are replaced using Finder's Glob engine.
     */
    protected function toRegexMatchStart(Path $path): Path
    {
        $glob = Glob::toRegex($path->toString(), true, true, '#');

        return new Path($glob);
    }
}
