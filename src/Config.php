<?php

namespace ZeroGravity\Cms;

class Config
{
    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var string|null
     */
    private $defaultLocale;

    /**
     * @var string[]
     */
    private $locales = [];

    /**
     * @var array
     */
    private $defaultSettings = [];

    /**
     * @var string|null
     */
    private $defaultLayoutTemplate;

    /**
     * @var string|null
     */
    private $defaultPageController = 'ZeroGravity\Cms\Controller\PageController::pageAction';

    /**
     * @var bool
     */
    private $multiLangEnabled = false;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @param string $storagePath
     *
     * @return Config
     */
    public function setStoragePath(string $storagePath): self
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    /**
     * @param null|string $defaultLocale
     *
     * @return Config
     */
    public function setDefaultLocale(?string $defaultLocale): self
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * @param string[] $locales
     *
     * @return Config
     */
    public function setLocales(array $locales): self
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return $this->defaultSettings;
    }

    /**
     * @param array $defaultSettings
     *
     * @return Config
     */
    public function setDefaultSettings(array $defaultSettings): self
    {
        $this->defaultSettings = $defaultSettings;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultLayoutTemplate(): ?string
    {
        return $this->defaultLayoutTemplate;
    }

    /**
     * @param null|string $defaultLayoutTemplate
     *
     * @return Config
     */
    public function setDefaultLayoutTemplate(?string $defaultLayoutTemplate): self
    {
        $this->defaultLayoutTemplate = $defaultLayoutTemplate;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultPageController(): ?string
    {
        return $this->defaultPageController;
    }

    /**
     * @param null|string $defaultPageController
     *
     * @return Config
     */
    public function setDefaultPageController(?string $defaultPageController): self
    {
        $this->defaultPageController = $defaultPageController;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiLangEnabled(): bool
    {
        return $this->multiLangEnabled;
    }

    /**
     * @param bool $multiLangEnabled
     *
     * @return Config
     */
    public function setMultiLangEnabled(bool $multiLangEnabled): self
    {
        $this->multiLangEnabled = $multiLangEnabled;

        return $this;
    }
}
