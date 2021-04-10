<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use DateTime;
use Exception;
use FilterIterator;
use InvalidArgumentException;
use Iterator;
use Symfony\Component\Finder\Comparator\Comparator;
use Symfony\Component\Finder\Comparator\DateComparator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use ZeroGravity\Cms\Content\Finder\Comparator\StringComparator;
use ZeroGravity\Cms\Content\Page;

/**
 * ExtraFilterIterator filters out pages that do not match the required extra setting value.
 *
 * @method Page current()
 */
class ExtraFilterIterator extends FilterIterator
{
    const COMPARATOR_STRING = 'string';
    const COMPARATOR_DATE = 'date';
    const COMPARATOR_NUMERIC = 'number';

    private array $extras;

    private array $notExtras;

    /**
     * @param Iterator $iterator The Iterator to filter
     */
    public function __construct(Iterator $iterator, array $extras, array $notExtras)
    {
        $this->extras = $extras;
        $this->notExtras = $notExtras;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        $page = $this->current();
        foreach ($this->extras as $extraSet) {
            if (!$this->compareExtra($extraSet, $page)) {
                return false;
            }
        }

        foreach ($this->notExtras as $extraSet) {
            if ($this->compareExtra($extraSet, $page)) {
                return false;
            }
        }

        return true;
    }

    private function compareExtra(array $extraSet, Page $page): bool
    {
        $key = $extraSet[0];
        $target = $extraSet[1];
        $comparator = $this->getComparator($extraSet[2], $target);
        $value = $this->getExtraValue($page, $key, $extraSet[2]);

        return $comparator->test($value);
    }

    private function getExtraValue(Page $page, $key, $type)
    {
        $value = $page->getExtra($key);
        if (null === $value) {
            return $value;
        }

        switch ($type) {
            case self::COMPARATOR_NUMERIC:
                return (int) $value;

            case self::COMPARATOR_DATE:
                if (is_int($value)) {
                    return $value;
                }

                try {
                    return (new DateTime($value))->format('U');
                } catch (Exception $e) {
                    return null;
                }
        }

        return $value;
    }

    /**
     * @param $name
     * @param $target
     *
     * @return Comparator
     */
    private function getComparator($name, $target)
    {
        switch ($name) {
            case self::COMPARATOR_STRING:
                $class = StringComparator::class;
                break;

            case self::COMPARATOR_DATE:
                $class = DateComparator::class;
                break;

            case self::COMPARATOR_NUMERIC:
                $class = NumberComparator::class;
                break;

            default:
                throw new InvalidArgumentException('Invalid comparator name: '.$name);
        }

        return new $class($target);
    }
}
