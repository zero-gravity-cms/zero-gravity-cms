<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

final class ExtraFilter
{
    public const COMPARATOR_STRING = 'string';
    public const COMPARATOR_DATE = 'date';
    public const COMPARATOR_NUMERIC = 'number';

    private string $name;
    private $value;
    private string $comparator;
    private bool $inverted;

    public static function has(string $name, $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, false);
    }

    public static function hasNot(string $name, $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, true);
    }

    private function __construct(string $name, $value, string $comparator, bool $inverted)
    {
        $this->name = $name;
        $this->value = $value;
        $this->comparator = $comparator;
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

    public function comparator(): string
    {
        return $this->comparator;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }
}
