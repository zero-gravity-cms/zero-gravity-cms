<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder;

use DateTime;
use stdClass;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Finder\FilterRegistry;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Finder\PageFinderFilter;
use ZeroGravity\Cms\Content\Finder\PageFinderFilters;
use ZeroGravity\Cms\Exception\FilterException;

/**
 * @group filter
 */
class FilterRegistryTest extends BaseUnit
{
    /**
     * @test
     */
    public function throwsExceptionIfNotValidType(): void
    {
        $registry = new FilterRegistry();

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', new DateTime());
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function allowsPageFinderFilterToBeAdded(): void
    {
        $registry = new FilterRegistry();
        $filter = $this->createMock(PageFinderFilter::class);
        $registry->addFilter('somename', $filter);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function allowsCallableToBeAdded(): void
    {
        $registry = new FilterRegistry();
        $filter = function () {};
        $registry->addFilter('somename', $filter);
    }

    /**
     * @test
     */
    public function throwsExceptionIfFilterAlreadyExists(): void
    {
        $registry = new FilterRegistry();
        $registry->addFilter('somename', function () {});

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', function () {});
    }

    /**
     * @test
     */
    public function callableFilterWillBeApplied(): void
    {
        $filter = $this->getMockBuilder(stdClass::class)
            ->setMethods(['myMethod'])
            ->getMock()
        ;
        $filter->expects(static::once())
            ->method('myMethod')
            ->willReturnArgument(0)
        ;

        $registry = new FilterRegistry();
        $registry->addFilter('somename', [$filter, 'myMethod']);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        static::assertSame($pageFinder, $resultFinder);
    }

    /**
     * @test
     */
    public function pageFinderFilterInstanceWillBeApplied(): void
    {
        $filter = $this->getMockBuilder(PageFinderFilter::class)
            ->setMethods(['apply'])
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

        static::assertSame($pageFinder, $resultFinder);
    }

    /**
     * @test
     */
    public function throwsExceptionIfFilterToApplyDoesNotExist(): void
    {
        $registry = new FilterRegistry();
        $pageFinder = new PageFinder();

        $this->expectException(FilterException::class);
        $registry->applyFilter($pageFinder, 'somename', []);
    }

    /**
     * @test
     */
    public function pageFinderFiltersObjectCanBeRegisteredAndApplied(): void
    {
        $filter = fn (PageFinder $finder) => $finder;

        $filters = $this->getMockBuilder(PageFinderFilters::class)
            ->setMethods(['getFilters'])
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

        static::assertSame($pageFinder, $resultFinder);
    }
}
