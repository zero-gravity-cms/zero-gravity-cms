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
     * A page is considered published if the following criteria are met:
     *
     * - 'publish' flag is true (default)
     * - 'publish_date' is either null or in the past
     * - 'unpublish_date' is either null or in the future
     *
     * The parent's publishing state is not taken into account!
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
