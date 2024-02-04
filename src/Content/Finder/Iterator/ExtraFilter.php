<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Webmozart\Assert\Assert;

final readonly class ExtraFilter
{
    public const COMPARATOR_STRING = 'string';
    public const COMPARATOR_DATE = 'date';
    public const COMPARATOR_NUMERIC = 'number';

    public static function has(string $name, mixed $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, false);
    }

    public static function hasNot(string $name, mixed $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, true);
    }

    private function __construct(
        private string $name,
        private mixed $value,
        private string $comparator,
        private bool $inverted,
    ) {
        Assert::oneOf($this->comparator, [
            self::COMPARATOR_DATE,
            self::COMPARATOR_NUMERIC,
            self::COMPARATOR_STRING,
        ]);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): mixed
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
