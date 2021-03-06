<?php

namespace ZeroGravity\Cms\Content;

class PageDiff
{
    /**
     * @var WritablePage
     */
    private $old;

    /**
     * @var WritablePage
     */
    private $new;

    /**
     * @param WritablePage $old
     * @param WritablePage $new
     */
    public function __construct(WritablePage $old, WritablePage $new)
    {
        $this->old = $old;
        $this->new = $new;
    }

    /**
     * @return WritablePage
     */
    public function getOld(): WritablePage
    {
        return $this->old;
    }

    /**
     * @return WritablePage
     */
    public function getNew(): WritablePage
    {
        return $this->new;
    }

    /**
     * @return bool
     */
    public function filesystemPathHasChanged(): bool
    {
        return $this->old->getFilesystemPath()->toString() !== $this->new->getFilesystemPath()->toString();
    }

    /**
     * @return string
     */
    public function getNewFilesystemPath(): string
    {
        return $this->new->getFilesystemPath();
    }

    /**
     * @return bool
     */
    public function settingsHaveChanged(): bool
    {
        return $this->old->getSettings() != $this->new->getSettings();
    }

    /**
     * @return array
     */
    public function getNewNonDefaultSettings(): array
    {
        return $this->new->getNonDefaultSettings();
    }

    /**
     * @return bool
     */
    public function contentHasChanged(): bool
    {
        return $this->old->getContentRaw() !== $this->new->getContentRaw();
    }

    /**
     * @return string
     */
    public function getNewContentRaw(): string
    {
        return $this->new->getContentRaw();
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function containsInstancesOf(string $class): bool
    {
        return $this->old instanceof $class && $this->new instanceof $class;
    }
}
