<?php

namespace ZeroGravity\Cms\Content;

final readonly class PageDiff
{
    public function __construct(
        private WritablePage $old,
        private WritablePage $new,
    ) {
    }

    public function getOld(): WritablePage
    {
        return $this->old;
    }

    public function getNew(): WritablePage
    {
        return $this->new;
    }

    public function filesystemPathHasChanged(): bool
    {
        return $this->old->getFilesystemPath()->toString() !== $this->new->getFilesystemPath()->toString();
    }

    public function getNewFilesystemPath(): string
    {
        return $this->new->getFilesystemPath();
    }

    public function settingsHaveChanged(): bool
    {
        return $this->old->getSettings(true) !== $this->new->getSettings(true);
    }

    public function getNewNonDefaultSettings(): array
    {
        return $this->new->getNonDefaultSettings();
    }

    public function contentHasChanged(): bool
    {
        return $this->old->getContentRaw() !== $this->new->getContentRaw();
    }

    public function getNewContentRaw(): string
    {
        return $this->new->getContentRaw();
    }

    public function containsInstancesOf(string $class): bool
    {
        return $this->old instanceof $class && $this->new instanceof $class;
    }
}
