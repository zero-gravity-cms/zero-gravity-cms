<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Closure;
use FilterIterator;
use InvalidArgumentException;
use Iterator;
use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * CustomFilterIterator filters pages by applying anonymous functions.
 *
 * The anonymous function receives a ReadablePage and must return false
 * to remove files.
 *
 * @extends FilterIterator<string, ReadablePage, Iterator<string, ReadablePage>>
 */
class CustomPageFilterIterator extends FilterIterator
{
    /**
     * @var list<Closure>
     */
    private array $filters = [];

    /**
     * @param Iterator<string, ReadablePage> $iterator The Iterator to filter
     * @param list<Closure>                  $filters  An array of PHP callbacks
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Iterator $iterator, array $filters)
    {
        /* @phpstan-ignore-next-line */
        Assert::allIsInstanceOf($filters, Closure::class, 'Invalid PHP callback.');
        $this->filters = $filters;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $page = $this->current();

        foreach ($this->filters as $filter) {
            if (false === $filter($page)) {
                return false;
            }
        }

        return true;
    }
}
