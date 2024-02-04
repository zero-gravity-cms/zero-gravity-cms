<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use InvalidArgumentException;
use Iterator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use ZeroGravity\Cms\Content\Page;

/**
 * FileCountFilterIterator filters out pages that do not contain the given number of files.
 *
 * @method Page current()
 */
final class FileCountFilterIterator extends FilterIterator
{
    public const MODE_FILES = 'files';
    public const MODE_IMAGES = 'images';
    public const MODE_DOCUMENTS = 'documents';

    /**
     * @param Iterator           $iterator    The Iterator to filter
     * @param NumberComparator[] $comparators An array of DateComparator instances
     */
    public function __construct(
        Iterator $iterator,
        private readonly array $comparators,
        private readonly string $mode = self::MODE_FILES,
    ) {
        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        $count = match ($this->mode) {
            self::MODE_FILES => count($this->current()->getFiles()),
            self::MODE_IMAGES => count($this->current()->getImages()),
            self::MODE_DOCUMENTS => count($this->current()->getDocuments()),
            default => throw new InvalidArgumentException('Unknown file count mode: '.$this->mode),
        };

        foreach ($this->comparators as $compare) {
            if (!$compare->test($count)) {
                return false;
            }
        }

        return true;
    }
}
