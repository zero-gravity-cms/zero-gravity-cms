<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use InvalidArgumentException;
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
class ExtraFilterIterator extends \FilterIterator
{
    const COMPARATOR_STRING = 'string';
    const COMPARATOR_DATE = 'date';
    const COMPARATOR_NUMERIC = 'number';

    /**
     * @var array
     */
    private $extras;

    /**
     * @var array
     */
    private $notExtras;

    /**
     * @param \Iterator $iterator  The Iterator to filter
     * @param array     $extras
     * @param array     $notExtras
     */
    public function __construct(\Iterator $iterator, array $extras, array $notExtras)
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
            $key = $extraSet[0];
            $target = $extraSet[1];
            $comparator = $this->getComparator($extraSet[2], $target);
            $value = $this->getExtraValue($page, $key, $extraSet[2]);

            if (!$comparator->test($value)) {
                return false;
            }
        }

        foreach ($this->notExtras as $extraSet) {
            $key = $extraSet[0];
            $target = $extraSet[1];
            $comparator = $this->getComparator($extraSet[2], $target);
            $value = $this->getExtraValue($page, $key, $extraSet[2]);

            if ($comparator->test($value)) {
                return false;
            }
        }

        return true;
    }

    private function getExtraValue(Page $page, $key, $type)
    {
        $value = $page->getExtraValue($key);
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
                    return (new \DateTime($value))->format('U');
                } catch (\Exception $e) {
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
