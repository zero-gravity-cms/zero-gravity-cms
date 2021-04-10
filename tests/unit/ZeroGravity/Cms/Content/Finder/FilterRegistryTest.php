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
    public function throwsExceptionIfNotValidType()
    {
        $registry = new FilterRegistry();

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', new DateTime());
    }

    /**
     * @test
     */
    public function allowsPageFinderFilterToBeAdded()
    {
        $registry = new FilterRegistry();
        $filter = $this->getMockBuilder(PageFinderFilter::class)->getMock();
        $registry->addFilter('somename', $filter);
    }

    /**
     * @test
     */
    public function allowsCallableToBeAdded()
    {
        $registry = new FilterRegistry();
        $filter = function () {};
        $registry->addFilter('somename', $filter);
    }

    /**
     * @test
     */
    public function throwsExceptionIfFilterAlreadyExists()
    {
        $registry = new FilterRegistry();
        $registry->addFilter('somename', function () {});

        $this->expectException(FilterException::class);
        $registry->addFilter('somename', function () {});
    }

    /**
     * @test
     */
    public function callableFilterWillBeApplied()
    {
        $filter = $this->getMockBuilder(stdClass::class)
            ->setMethods(['myMethod'])
            ->getMock()
        ;
        $filter->expects($this->once())
            ->method('myMethod')
            ->will($this->returnArgument(0))
        ;

        $registry = new FilterRegistry();
        $registry->addFilter('somename', [$filter, 'myMethod']);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        $this->assertSame($pageFinder, $resultFinder);
    }

    /**
     * @test
     */
    public function pageFinderFilterInstanceWillBeApplied()
    {
        $filter = $this->getMockBuilder(PageFinderFilter::class)
            ->setMethods(['apply'])
            ->getMock()
        ;
        $filter->expects($this->once())
            ->method('apply')
            ->will($this->returnArgument(0))
        ;

        $registry = new FilterRegistry();
        $registry->addFilter('somename', $filter);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        $this->assertSame($pageFinder, $resultFinder);
    }

    /**
     * @test
     */
    public function throwsExceptionIfFilterToApplyDoesNotExist()
    {
        $registry = new FilterRegistry();
        $pageFinder = new PageFinder();

        $this->expectException(FilterException::class);
        $registry->applyFilter($pageFinder, 'somename', []);
    }

    /**
     * @test
     */
    public function pageFinderFiltersObjectCanBeRegisteredAndApplied()
    {
        $filter = fn (PageFinder $finder) => $finder;

        $filters = $this->getMockBuilder(PageFinderFilters::class)
            ->setMethods(['getFilters'])
            ->getMock()
        ;
        $filters->expects($this->once())
            ->method('getFilters')
            ->willReturn(['somename' => $filter])
        ;

        $registry = new FilterRegistry();
        $registry->addFilters($filters);

        $pageFinder = new PageFinder();
        $options = ['some' => 'option'];
        $resultFinder = $registry->applyFilter($pageFinder, 'somename', $options);

        $this->assertSame($pageFinder, $resultFinder);
    }
}
