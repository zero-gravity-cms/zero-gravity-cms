<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use DateTime;
use DateTimeInterface;
use Exception;
use FilterIterator;
use Iterator;
use Stringable;
use Symfony\Component\Finder\Comparator\Comparator;
use Symfony\Component\Finder\Comparator\DateComparator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use Traversable;
use ZeroGravity\Cms\Content\Finder\Comparator\StringComparator;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Exception\FilterException;

/**
 * ExtraFilterIterator filters out pages that do not match the required extra setting value.
 *
 * @method ReadablePage current()
 *
 * @extends FilterIterator<string, ReadablePage, Traversable<string, ReadablePage>>
 */
final class ExtraFilterIterator extends FilterIterator
{
    /**
     * @param Iterator      $iterator The Iterator to filter
     * @param ExtraFilter[] $extras
     */
    public function __construct(
        Iterator $iterator,
        private readonly array $extras,
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
        $page = $this->current();
        foreach ($this->extras as $extraFilter) {
            $isInverted = $extraFilter->isInverted();
            $valuesMatch = $this->compareExtra($extraFilter, $page);

            if ($isInverted === $valuesMatch) {
                return false;
            }
        }

        return true;
    }

    private function compareExtra(ExtraFilter $extraFilter, ReadablePage $page): bool
    {
        $comparator = $this->getComparator($extraFilter);
        $value = $this->getExtraValue($page, $extraFilter);

        return $comparator->test($value);
    }

    private function getExtraValue(ReadablePage $page, ExtraFilter $extraFilter): string|int|null
    {
        $value = $page->getExtra($extraFilter->name());
        if (null === $value) {
            return null;
        }

        switch ($extraFilter->comparator()) {
            case ExtraFilter::COMPARATOR_NUMERIC:
                if (empty($value)) {
                    return 0;
                }
                if (!is_scalar($value)) {
                    throw FilterException::notAScalar($value);
                }

                return (int) $value;

            case ExtraFilter::COMPARATOR_DATE:
                if ($value instanceof DateTimeInterface) {
                    return $value->getTimestamp();
                }
                if (is_int($value)) {
                    return $value;
                }
                if (!is_scalar($value) && !$value instanceof Stringable) {
                    throw FilterException::notAString($value);
                }

                try {
                    return (new DateTime((string) $value))->getTimestamp();
                } catch (Exception) {
                    return null;
                }
        }

        if (!is_scalar($value) && !$value instanceof Stringable) {
            throw FilterException::notAString($value);
        }

        return (string) $value;
    }

    private function getComparator(ExtraFilter $extraFilter): Comparator
    {
        $comparatorName = $extraFilter->comparator();
        $class = match ($comparatorName) {
            ExtraFilter::COMPARATOR_STRING => StringComparator::class,
            ExtraFilter::COMPARATOR_DATE => DateComparator::class,
            ExtraFilter::COMPARATOR_NUMERIC => NumberComparator::class,
        };

        return new $class($extraFilter->value());
    }
}
