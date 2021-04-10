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

    private array $comparators = [];

    private string $mode;

    /**
     * @param Iterator           $iterator    The Iterator to filter
     * @param NumberComparator[] $comparators An array of DateComparator instances
     * @param string             $mode
     */
    public function __construct(Iterator $iterator, array $comparators, $mode = self::MODE_FILES)
    {
        $this->comparators = $comparators;
        $this->mode = $mode;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        switch ($this->mode) {
            case self::MODE_FILES:
                $count = count($this->current()->getFiles());
                break;
            case self::MODE_IMAGES:
                $count = count($this->current()->getImages());
                break;
            case self::MODE_DOCUMENTS:
                $count = count($this->current()->getDocuments());
                break;
            default:
                throw new InvalidArgumentException('Unknown file count mode: '.$this->mode);
        }

        foreach ($this->comparators as $compare) {
            if (!$compare->test($count)) {
                return false;
            }
        }

        return true;
    }
}
