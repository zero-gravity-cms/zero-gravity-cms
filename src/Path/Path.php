<?php

namespace ZeroGravity\Cms\Path;

use Webmozart\Assert\Assert;

final class Path
{
    private ?string $pathString = null;
    private bool $isAbsolute = false;
    private bool $isDirectory = false;

    /**
     * @var PathElement[]
     */
    private array $elements = [];

    /**
     * Create a new Path object using the path string.
     */
    public function __construct(string $pathString)
    {
        $this->parsePathString($pathString);
    }

    /**
     * Resolve relative reference ('../') in this path.
     *
     * @param Path|null $parentPath
     */
    public function normalize(self $parentPath = null): void
    {
        if ($this->isRegex()) {
            return;
        }

        PathNormalizer::normalizePath($this, $parentPath);
    }

    public function isAbsolute(): bool
    {
        return $this->isAbsolute;
    }

    public function isDirectory(): bool
    {
        return $this->isDirectory;
    }

    public function isRegex(): bool
    {
        foreach ($this->getElements() as $element) {
            if ($element->isRegex()) {
                return true;
            }
        }

        return false;
    }

    public function isGlob(): bool
    {
        if ($this->isRegex()) {
            return false;
        }

        foreach ($this->getElements() as $element) {
            if ($element->isGlob()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return PathElement[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function toString(bool $forceRebuild = false): string
    {
        if ($forceRebuild) {
            $this->rebuildString();
        }

        return $this->pathString;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function hasElements(): bool
    {
        return count($this->getElements()) > 0;
    }

    public function isSingleElement(): bool
    {
        return 1 === count($this->getElements());
    }

    /**
     * @param PathElement[] $elements
     */
    public function setElements(array $elements): void
    {
        Assert::allIsInstanceOf($elements, PathElement::class);
        $this->elements = $elements;
        $this->rebuildString();
    }

    public function appendElement(PathElement $element): void
    {
        $this->elements[] = $element;
        $this->rebuildString();
    }

    /**
     * Create a new Path instance where the elements of the given child Path have been appended.
     *
     * @param Path $childPath
     *
     * @return Path
     */
    public function appendPath(self $childPath): self
    {
        $path = clone $this;
        foreach ($childPath->getElements() as $element) {
            $path->appendElement($element);
        }
        $path->isDirectory = $childPath->isDirectory();
        $path->rebuildString();

        return $path;
    }

    /**
     * Get a new Path instance holding the bottommost directory of the given Path.
     * If the path already is a directory, the new Path will be the same. If it
     * is not, the new Path will contain everything but the last element.
     */
    public function getDirectory(): self
    {
        $directory = clone $this;
        if ($directory->isDirectory()) {
            return $directory;
        }

        $directory->isDirectory = true;
        if (!$directory->hasElements()) {
            $directory->rebuildString();

            return $directory;
        }

        array_pop($directory->elements);
        $directory->rebuildString();

        return $directory;
    }

    /**
     * Get a new Path instance holding the last element of the given Path. This is the opposite to Path::getDirectory().
     * If the Path is a directory, null is returned.
     *
     * @see https://stackoverflow.com/a/35957563/22592
     *
     * @return Path|null
     */
    public function getFile(): ?self
    {
        if ($this->isDirectory()) {
            return null;
        }

        $lastElement = $this->getLastElement();
        if (null === $lastElement) {
            return null;
        }

        return new self($lastElement->getName());
    }

    /**
     * Get the last PathElement of this path.
     * Returns null if Path is empty.
     */
    public function getLastElement(): ?PathElement
    {
        if ($this->hasElements()) {
            return array_values(array_slice($this->elements, -1))[0];
        }

        return null;
    }

    /**
     * Drop the last element from the path.
     */
    public function dropLastElement(): void
    {
        array_pop($this->elements);
        $this->isDirectory = true;
        $this->rebuildString();
    }

    /**
     * Parse the configured path string.
     */
    private function parsePathString(string $pathString): void
    {
        $this->pathString = $pathString;

        if (StringPatternUtil::stringContainsRegex($pathString)) {
            $this->initAsRegex($pathString);

            return;
        }

        $this->initDefault($pathString);
    }

    private function initAsRegex(string $pathString): void
    {
        $this->isAbsolute = false;
        $this->isDirectory = false;

        $this->elements = [
            $this->createRegexElement($pathString),
        ];
    }

    private function initDefault(string $pathString): void
    {
        $this->isAbsolute = (0 === strpos($pathString, '/'));
        $this->isDirectory = (strlen($pathString) - 1 === strrpos($pathString, '/'));

        $parts = array_filter(explode('/', $pathString), fn ($part) => !empty($part) && '.' !== $part);

        $this->elements = array_map(fn ($part) => $this->createElement($part), $parts);
    }

    /**
     * Create a new PathElement instance.
     */
    private function createElement(string $part): PathElement
    {
        return new PathElement($part);
    }

    /**
     * Create a new PathElement instance that is allowed to contain regex.
     */
    private function createRegexElement(string $part): PathElement
    {
        return new PathElement($part, true);
    }

    /**
     * Rebuild path string from elements and settings.
     */
    private function rebuildString(): void
    {
        $path = $this->isAbsolute() ? '/' : '';
        $path .= implode('/', $this->getElements());
        $path .= $this->isDirectory() ? '/' : '';
        if ('//' === $path) {
            $path = '/';
        }

        $this->pathString = $path;
    }
}
