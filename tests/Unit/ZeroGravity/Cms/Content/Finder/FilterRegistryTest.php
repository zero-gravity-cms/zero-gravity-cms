<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder;

use Codeception\Attribute\Group;
use DateTime;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Finder\FilterRegistry;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Finder\PageFinderFilter;
use ZeroGravity\Cms\Content\Finder\PageFinderFilters;
use ZeroGravity\Cms\Exception\FilterException;

#[Group('filter')]
class FilterRegistryTest extends BaseUnit
{
    #[Test]
    public function throwsExceptionIfNotValidType(): void
    {
        $registry = new FilterRegistry();

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', new DateTime());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function allowsPageFinderFilterToBeAdded(): void
    {
        $registry = new FilterRegistry();
        $filter = $this->createMock(PageFinderFilter::class);
        $registry->addFilter('somename', $filter);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function allowsCallableToBeAdded(): void
    {
        $registry = new FilterRegistry();
        $filter = static function (): void {
        };
        $registry->addFilter('somename', $filter);
    }

    #[Test]
    public function throwsExceptionIfFilterAlreadyExists(): void
    {
        $registry = new FilterRegistry();
        $registry->addFilter('somename', static function (): void {
        });

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', static function (): void {
        });
    }

    #[Test]
    public function callableFilterWillBeApplied(): void
    {
        $filter = $this->createMock(MockFilter::class);
        $filter->expects(static::once())
            ->method('myMethod')
            ->willReturnArgument(0)
        ;

        $registry = new FilterRegistry();
        $registry->addFilter('somename', [$filter, 'myMethod']);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        self::assertSame($pageFinder, $resultFinder);
    }

    #[Test]
    public function pageFinderFilterInstanceWillBeApplied(): void
    {
        $filter = $this->getMockBuilder(PageFinderFilter::class)
            ->onlyMethods(['apply'])
            ->getMock()
        ;
        $filter->expects(static::once())
            ->method('apply')
            ->willReturnArgument(0)
        ;

        $registry = new FilterRegistry();
        $registry->addFilter('somename', $filter);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        self::assertSame($pageFinder, $resultFinder);
    }

    #[Test]
    public function throwsExceptionIfFilterToApplyDoesNotExist(): void
    {
        $registry = new FilterRegistry();
        $pageFinder = new PageFinder();

        $this->expectException(FilterException::class);
        $registry->applyFilter($pageFinder, 'somename', []);
    }

    #[Test]
    public function pageFinderFiltersObjectCanBeRegisteredAndApplied(): void
    {
        $filter = static fn (PageFinder $finder): PageFinder => $finder;

        $filters = $this->getMockBuilder(PageFinderFilters::class)
            ->onlyMethods(['getFilters'])
            ->getMock()
        ;
        $filters->expects(static::once())
            ->method('getFilters')
            ->willReturn(['somename' => $filter])
        ;

        $registry = new FilterRegistry();
        $registry->addFilters($filters);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        self::assertSame($pageFinder, $resultFinder);
    }
}
