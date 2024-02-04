<?php

namespace ZeroGravity\Cms\Content\Finder\Tester;

use ZeroGravity\Cms\Content\ReadablePage;

final readonly class TaxonomyTester
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR = 'OR';
    private string $mode;

    /**
     * @param list<string> $values
     */
    public static function has(string $name, array $values, ?string $mode): self
    {
        return new self($name, $values, $mode, false);
    }

    /**
     * @param list<string> $values
     */
    public static function hasNot(string $name, array $values, ?string $mode): self
    {
        return new self($name, $values, $mode, true);
    }

    /**
     * @param list<string> $values
     */
    public function __construct(
        private string $name,
        private array $values,
        ?string $mode,
        private bool $inverted,
    ) {
        $this->mode = $mode ?? self::OPERATOR_AND;
    }

    /**
     * Return true if value matches the taxonomies to test against, false if not.
     */
    public function pageMatchesTaxonomy(ReadablePage $page): bool
    {
        $pageValues = $page->getTaxonomy($this->name);

        if (self::OPERATOR_OR === $this->mode) {
            return $this->testOr($pageValues);
        }

        return $this->testAnd($pageValues);
    }

    /**
     * @param list<string> $pageValues
     */
    private function testOr(array $pageValues): bool
    {
        foreach ($this->values as $value) {
            if (in_array($value, $pageValues, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $pageValues
     */
    private function testAnd(array $pageValues): bool
    {
        foreach ($this->values as $value) {
            if (!in_array($value, $pageValues, true)) {
                return false;
            }
        }

        return true;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }
}
