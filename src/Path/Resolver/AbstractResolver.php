<?php

namespace ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Finder\Glob;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Exception\ResolverException;
use ZeroGravity\Cms\Path\Path;

abstract class AbstractResolver implements PathResolver
{
    /**
     * Find a single matching file.
     *
     * @param Path      $path
     * @param Path|null $parentPath
     * @param bool      $strict     Accept only 1 found file, throw ResolverException if more than 1
     *
     * @return null|File
     */
    public function findOne(Path $path, Path $parentPath = null, bool $strict = true): ? File
    {
        $file = $this->get(clone $path, (null !== $parentPath) ? clone $parentPath : null);
        if (null !== $file) {
            return $file;
        }

        $files = $this->find(clone $path, (null !== $parentPath) ? clone $parentPath : null);
        if (0 === count($files)) {
            return null;
        } elseif (1 === count($files)) {
            return array_shift($files);
        } elseif (count($files) > 1 && !$strict) {
            return array_shift($files);
        }

        throw ResolverException::moreThanOneFileMatchingPattern($path->toString(), $files);
    }

    /**
     * @param Path      $path
     * @param Path|null $parentPath
     */
    protected function moveNonGlobsToParent(Path $path, Path $parentPath)
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
     *
     * @param Path $path
     *
     * @return Path
     */
    protected function toRegexMatchStart(Path $path): Path
    {
        $glob = Glob::toRegex($path->toString(), true, true, '#');

        return new Path($glob);
    }
}
