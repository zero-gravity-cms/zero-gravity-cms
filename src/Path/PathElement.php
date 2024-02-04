<?php

namespace ZeroGravity\Cms\Path;

use Stringable;

final class PathElement implements Stringable
{
    private string $name;
    private bool $isRegex = false;
    private bool $isGlob = false;

    /**
     * Create a new PathElement.
     *
     * @param string $name       Path element name/content
     * @param bool   $allowRegex Allow this element to contain a regular expression that will be used as such
     */
    public function __construct(string $name, bool $allowRegex = false)
    {
        $this->init($name, $allowRegex);
    }

    private function init(string $name, bool $allowRegex = false): void
    {
        $this->name = $name;
        $this->isRegex = $allowRegex && !$this->isParentReference() && StringPatternUtil::stringContainsRegex($name);
        $this->isGlob = !$this->isRegex && StringPatternUtil::stringContainsGlob($name);
    }

    public function isParentReference(): bool
    {
        return '..' === $this->name;
    }

    public function isGlob(): bool
    {
        return $this->isGlob;
    }

    public function isRegex(): bool
    {
        return $this->isRegex;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
