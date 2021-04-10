<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;

/**
 * This trait contains settings getters related to page publishing status.
 */
trait PagePublishingTrait
{
    abstract public function getSetting(string $name);

    /**
     * Get optional publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getPublishDate(): ?DateTimeImmutable
    {
        return $this->getSetting('publish_date');
    }

    /**
     * Get optional un-publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getUnpublishDate(): ?DateTimeImmutable
    {
        return $this->getSetting('unpublish_date');
    }

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
        if ($this->unpublishDateIsInPast()) {
            return false;
        }

        return true;
    }

    private function publishDateIsInFuture(): bool
    {
        if (null === $this->getPublishDate()) {
            return false;
        }
        if ($this->getPublishDate()->format('U') > time()) {
            return true;
        }

        return false;
    }

    private function unpublishDateIsInPast(): bool
    {
        if (null === $this->getUnpublishDate()) {
            return false;
        }
        if ($this->getUnpublishDate()->format('U') < time()) {
            return true;
        }

        return false;
    }
}
