<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

final readonly class SettingFilter
{
    public static function has(string $name, mixed $value): self
    {
        return new self($name, $value, false);
    }

    public static function hasNot(string $name, mixed $value): self
    {
        return new self($name, $value, true);
    }

    private function __construct(
        private string $name,
        private mixed $value,
        private bool $inverted,
    ) {
    }

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
