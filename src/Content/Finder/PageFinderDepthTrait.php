<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Comparator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use Symfony\Component\Finder\Iterator\DepthRangeFilterIterator;

trait PageFinderDepthTrait
{
    /**
     * @var Comparator\NumberComparator[]
     */
    private array $depths = [];

    /**
     * Adds tests for the directory depth.
     *
     * Usage:
     *
     *   $finder->depth('> 1') // the Finder will start matching at level 1.
     *   $finder->depth('< 3') // the Finder will descend at most 3 levels of directories below the starting point.
     *
     * @param string|int $level The depth level expression
     *
     * @return $this
     *
     * @see DepthRangeFilterIterator
     * @see NumberComparator
     */
    public function depth($level)
    {
        $this->depths[] = new NumberComparator($level);

        return $this;
    }

    private function applyDepthsIterator(RecursiveIteratorIterator $iterator): Iterator
    {
        $minDepth = 0;
        $maxDepth = PHP_INT_MAX;

        foreach ($this->depths as $comparator) {
            $target = (int) $comparator->getTarget();

            switch ($comparator->getOperator()) {
                case '>':
                    $minDepth = $target + 1;
                    break;
                case '>=':
                    $minDepth = $target;
                    break;
                case '<':
                    $maxDepth = $target - 1;
                    break;
                case '<=':
                    $maxDepth = $target;
                    break;
                default:
                    $minDepth = $maxDepth = $target;
            }
        }

        if ($minDepth > 0 || $maxDepth < PHP_INT_MAX) {
            $iterator = new DepthRangeFilterIterator($iterator, $minDepth, $maxDepth);
        }

        return $iterator;
    }
}
