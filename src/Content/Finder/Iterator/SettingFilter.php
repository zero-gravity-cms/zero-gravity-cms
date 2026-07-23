<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\Meta\PageSettingsLoader;

final readonly class SettingFilter
{
    /**
     * @param PageSettingsLoader::KEY_* $name
     */
    public static function has(string $name, mixed $value): self
    {
        return new self($name, $value, false);
    }

    /**
     * @param PageSettingsLoader::KEY_* $name
     */
    public static function hasNot(string $name, mixed $value): self
    {
        return new self($name, $value, true);
    }

    private function __construct(
        /**
         * @var PageSettingsLoader::KEY_*
         */
        private string $name,
        private mixed $value,
        private bool $inverted,
    ) {
        Assert::true(PageSettingsLoader::isValidKey($this->name), 'Unknown setting key: '.$this->name);
    }

    /**
     * @return PageSettingsLoader::KEY_*
     */
    public function name(): string
    {
        return $this->name;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }
}
