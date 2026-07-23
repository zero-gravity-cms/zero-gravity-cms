<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Webmozart\Assert\Assert;

final readonly class ExtraFilter
{
    public const COMPARATOR_STRING = 'string';

    public const COMPARATOR_DATE = 'date';

    public const COMPARATOR_NUMERIC = 'number';

    /**
     * @param non-empty-string   $name
     * @param self::COMPARATOR_* $comparator
     */
    public static function has(string $name, string $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, false);
    }

    /**
     * @param non-empty-string   $name
     * @param self::COMPARATOR_* $comparator
     */
    public static function hasNot(string $name, string $value, string $comparator = self::COMPARATOR_STRING): self
    {
        return new self($name, $value, $comparator, true);
    }

    private function __construct(
        private string $name,
        private string $value,
        /**
         * @var self::COMPARATOR_*
         */
        private string $comparator,
        private bool $inverted,
    ) {
        Assert::notEmpty($this->name);
        /* @phpstan-ignore-next-line */
        Assert::oneOf($this->comparator, [
            self::COMPARATOR_DATE,
            self::COMPARATOR_NUMERIC,
            self::COMPARATOR_STRING,
        ]);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * @return self::COMPARATOR_*
     */
    public function comparator(): string
    {
        return $this->comparator;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }
}
