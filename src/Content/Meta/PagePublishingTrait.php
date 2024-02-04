<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;

/**
 * This trait contains settings getters related to page publishing status.
 */
trait PagePublishingTrait
{
    abstract public function getSetting(string $name): mixed;

    /**
     * Get optional publishing date of this page.
     */
    public function getPublishDate(): ?DateTimeImmutable
    {
        return $this->getSetting('publish_date');
    }

    /**
     * Get optional un-publishing date of this page.
     */
    public function getUnpublishDate(): ?DateTimeImmutable
    {
        return $this->getSetting('unpublish_date');
    }

    /**
     * All the publishing settings are okay.
     * By default, these are:
     * - 'publish'
     * - 'publish_date'
     * - 'unpublish_date'.
     */
    public function isPublished(): bool
    {
        if (!$this->getSetting('publish')) {
            return false;
        }

        return $this->isCurrentDateWithinPublishDates();
    }

    public function isCurrentDateWithinPublishDates(): bool
    {
        if ($this->publishDateIsInFuture()) {
            return false;
        }

        return !$this->unpublishDateIsInPast();
    }

    private function publishDateIsInFuture(): bool
    {
        $publishDate = $this->getPublishDate();
        if (null === $publishDate) {
            return false;
        }

        return $publishDate->format('U') > time();
    }

    private function unpublishDateIsInPast(): bool
    {
        $unpublishDate = $this->getUnpublishDate();
        if (null === $unpublishDate) {
            return false;
        }

        return $unpublishDate->format('U') < time();
    }
}
