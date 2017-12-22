<?php

namespace ZeroGravity\Cms\Content\Finder;

use Symfony\Component\Finder\Comparator;
use Symfony\Component\Finder\Iterator\DepthRangeFilterIterator;

trait PageFinderDepthTrait
{
    /**
     * @var Comparator\NumberComparator[]
     */
    private $depths = [];

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
        $this->depths[] = new Comparator\NumberComparator($level);

        return $this;
    }

    /**
     * @param \RecursiveIteratorIterator $iterator
     *
     * @return \Iterator
     */
    private function applyDepthsIterator(\RecursiveIteratorIterator $iterator): \Iterator
    {
        $minDepth = 0;
        $maxDepth = PHP_INT_MAX;

        foreach ($this->depths as $comparator) {
            switch ($comparator->getOperator()) {
                case '>':
                    $minDepth = (int) $comparator->getTarget() + 1;
                    break;
                case '>=':
                    $minDepth = $comparator->getTarget();
                    break;
                case '<':
                    $maxDepth = (int) $comparator->getTarget() - 1;
                    break;
                case '<=':
                    $maxDepth = $comparator->getTarget();
                    break;
                default:
                    $minDepth = $maxDepth = $comparator->getTarget();
            }
        }

        if ($minDepth > 0 || $maxDepth < PHP_INT_MAX) {
            $iterator = new DepthRangeFilterIterator($iterator, $minDepth, $maxDepth);
        }

        return $iterator;
    }
}
