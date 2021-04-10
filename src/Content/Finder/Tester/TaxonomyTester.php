<?php

namespace ZeroGravity\Cms\Content\Finder\Tester;

use ZeroGravity\Cms\Content\Page;

final class TaxonomyTester
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR = 'OR';

    private string $name;
    /**
     * @var string[]
     */
    private array $values;
    private string $mode;
    private bool $inverted;

    public static function has(string $name, array $values, ?string $mode): self
    {
        return new self($name, $values, $mode, false);
    }

    public static function hasNot(string $name, array $values, ?string $mode): self
    {
        return new self($name, $values, $mode, true);
    }

    public function __construct(string $name, array $values, ?string $mode, bool $inverted)
    {
        $this->name = $name;
        $this->values = $values;
        $this->mode = $mode ?? self::OPERATOR_AND;
        $this->inverted = $inverted;
    }

    /**
     * Return true if value matches the taxonomies to test against, false if not.
     */
    public function pageMatchesTaxonomy(Page $page): bool
    {
        $pageValues = $page->getTaxonomy($this->name);

        if (self::OPERATOR_OR === $this->mode) {
            return $this->testOr($pageValues);
        }

        return $this->testAnd($pageValues);
    }

    /**
     * @param string[] $pageValues
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
     * @param string[] $pageValues
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
