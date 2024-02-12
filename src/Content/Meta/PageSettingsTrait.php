<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * This trait contains settings related methods (mostly getters) of the Page class.
 * This helps to separate native properties from validated settings/options.
 *
 * @phpstan-import-type SettingValue from PageSettings
 * @phpstan-import-type SettingValues from PageSettings
 * @phpstan-import-type SerializedSettingValue from PageSettings
 * @phpstan-import-type SerializedSettingValues from PageSettings
 */
trait PageSettingsTrait
{
    protected PageSettings $settings;

    abstract public function getParent(): ?ReadablePage;

    /**
     * @param array<string, SettingValue> $settings
     */
    private function initSettings(array $settings, string $name): void
    {
        $this->settings = new PageSettings($settings, $name);
    }

    public function getSetting(string $name): mixed
    {
        return $this->settings->get($name);
    }

    public function getSlug(): string
    {
        return (string) $this->getSetting('slug');
    }

    /**
     * Check if this page has a custom slug that does not match its name.
     */
    public function hasCustomSlug(): bool
    {
        return array_key_exists('slug', $this->getNonDefaultSettings());
    }

    public function getTitle(): string
    {
        return (string) $this->getSetting('title');
    }

    public function getContentType(): string
    {
        return $this->getSetting('content_type');
    }

    /**
     * @param bool $serialize set true to convert all object setting types (e.g. dates) to primitive values
     *
     * @return ($serialize is true ? SerializedSettingValues : SettingValues)
     */
    public function getSettings(bool $serialize = false): array
    {
        return $this->settings->toArray($serialize);
    }

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
     *
     * @return ($serialize is true ? array<string, SerializedSettingValue> : array<string, SettingValue>)
     */
    public function getNonDefaultSettings(bool $serialize = false): array
    {
        $settings = $this->settings->getNonDefaultValues($serialize);
        if (null === $this->getParent()) {
            return $settings;
        }

        foreach ($this->getParent()->getChildDefaults() as $key => $childDefault) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            if ($settings[$key] === $childDefault) {
                unset($settings[$key]);
            }
        }

        return $settings;
    }

    /**
     * Get default setting values for child pages.
     *
     * @return array<string, SettingValue>
     */
    public function getChildDefaults(): array
    {
        return $this->getSetting('child_defaults');
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraValues(): array
    {
        return (array) $this->getSetting('extra');
    }

    public function getMenuId(): string|bool
    {
        return $this->getSetting('menu_id');
    }

    public function getMenuLabel(): string
    {
        if (!empty($this->getSetting('menu_label'))) {
            return (string) $this->getSetting('menu_label');
        }

        return $this->getTitle();
    }

    /**
     * Page is listed in menus.
     */
    public function isVisible(): bool
    {
        return (bool) $this->getSetting('visible');
    }

    /**
     * Page is considered a modular page, not a regular content page.
     * Modular pages are designated to contain a collection of sub content or "module" pages.
     */
    public function isModular(): bool
    {
        return (bool) $this->getSetting('modular');
    }

    /**
     * Page is considered a snippet to be embedded in a "modular" page.
     * This is achieved automatically by prefixing the directory with an underscore.
     *
     * Module pages will be hidden from menus.
     */
    public function isModule(): bool
    {
        return (bool) $this->getSetting('module');
    }

    /**
     * Get custom template to embed this page's content in.
     */
    public function getLayoutTemplate(): ?string
    {
        return $this->getSetting('layout_template');
    }

    /**
     * Get custom template for rendering the page content.
     */
    public function getContentTemplate(): ?string
    {
        return $this->getSetting('content_template');
    }

    /**
     * Get custom controller name to use for this page.
     */
    public function getController(): ?string
    {
        return (string) $this->getSetting('controller');
    }

    public function getExtra(string $name, mixed $default = null): mixed
    {
        $extra = $this->getExtraValues();
        if (array_key_exists($name, $extra)) {
            return $extra[$name];
        }

        return $default;
    }

    /**
     * Get optional date information of this page.
     */
    public function getDate(): ?DateTimeImmutable
    {
        return $this->getSetting('date');
    }
}
