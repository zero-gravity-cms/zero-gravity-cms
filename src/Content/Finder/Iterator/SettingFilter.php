<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

final class SettingFilter
{
    private string $name;
    private $value;
    private bool $inverted;

    public static function has(string $name, $value): self
    {
        return new self($name, $value, false);
    }

    public static function hasNot(string $name, $value): self
    {
        return new self($name, $value, true);
    }

    private function __construct(string $name, $value, bool $inverted)
    {
        $this->name = $name;
        $this->value = $value;
        $this->inverted = $inverted;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }
}
