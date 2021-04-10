<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\Iterator\SortableIterator;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;

/**
 * @group sort
 */
class SortableIteratorTest extends BaseUnit
{
    private ?\ZeroGravity\Cms\Content\Finder\PageFinder $finderPrototype = null;

    public function _before()
    {
        $mapper = $this->getValidPagesFilesystemMapper();
        $repository = new ContentRepository($mapper, new ArrayAdapter(), false);

        $this->finderPrototype = $repository->getPageFinder();
    }

    /**
     * @test
     * @dataProvider provideSortResults
     *
     * @param $method
     */
    public function sortMethodWorks($method, array $expectedElements)
    {
        $finder = $this->getFinder();
        if (is_string($method)) {
            $sortMethod = 'sortBy'.ucfirst($method);
            $finder->$sortMethod();
        } elseif (is_array($method)) {
            [$method, $parameter] = $method;
            $sortMethod = 'sortBy'.ucfirst($method);
            $finder->$sortMethod($parameter);
        } elseif (is_callable($method)) {
            $finder->sort($method);
        }

        $expectedKeys = array_keys($expectedElements);
        $keys = array_keys($finder->toArray());

        // dump($method);
        // $getter = 'get'.ucfirst($method);
        // dump(array_map(function (Page $p) use ($getter) { return (string) $p->$getter(); }, $finder->toArray()));

        static::assertSame($expectedKeys, $keys);
    }

    public function provideSortResults(): Iterator
    {
        $pagesSortedByName = [
            '/not_published/child1' => '01.child1',
            '/yaml_and_twig/child1' => '01.child1',
            '/yaml_only' => '01.yaml_only',
            '/yaml_and_twig/child2' => '02.child2',
            '/markdown_only' => '02.markdown_only',
            '/yaml_and_markdown_and_twig' => '03.yaml_and_markdown_and_twig',
            '/with_children' => '04.with_children',
            '/twig_only' => '05.twig_only',
            '/yaml_and_twig' => '06.yaml_and_twig',
            '/with_children/_child1' => '_child1',
            '/with_children/_child2' => '_child2',
            '/no_sorting_prefix' => 'no_sorting_prefix',
        ];
        $pagesSortedBySlug = [
            '/with_children/_child1' => '_child1',
            '/with_children/_child2' => '_child2',
            '/not_published/child1' => 'child1',
            '/yaml_and_twig/child1' => 'child1',
            '/yaml_and_twig/child2' => 'child2',
            '/markdown_only' => 'markdown_only',
            '/no_sorting_prefix' => 'no_sorting_prefix',
            '/twig_only' => 'twig_only',
            '/with_children' => 'with_children',
            '/yaml_and_markdown_and_twig' => 'yaml_and_markdown_and_twig',
            '/yaml_and_twig' => 'yaml_and_twig',
            '/yaml_only' => 'yaml_only',
        ];
        $pagesSortedByTitle = [
            '/not_published/child1' => 'Child1',
            '/with_children/_child1' => 'Child1',
            '/yaml_and_twig/child1' => 'Child1',
            '/with_children/_child2' => 'Child2',
            '/yaml_and_twig/child2' => 'Child2',
            '/markdown_only' => 'Markdown Only',
            '/no_sorting_prefix' => 'No Sorting Prefix',
            '/yaml_only' => 'testtitle',
            '/twig_only' => 'Twig Only',
            '/with_children' => 'With Children',
            '/yaml_and_markdown_and_twig' => 'Yaml And Markdown',
            '/yaml_and_twig' => 'Yaml And Twig',
        ];
        $pagesSortedByDate = [
            '/markdown_only' => null,
            '/no_sorting_prefix' => null,
            '/twig_only' => null,
            '/with_children' => null,
            '/with_children/_child1' => null,
            '/with_children/_child2' => null,
            '/yaml_and_twig' => null,
            '/yaml_and_twig/child1' => null,
            '/yaml_and_twig/child2' => null,
            '/not_published/child1' => '2016-10-01 00:00:00.000000',
            '/yaml_and_markdown_and_twig' => '2016-10-01 00:00:00.000000',
            '/yaml_only' => '2017-01-01 00:00:00.000000',
        ];
        $pagesSortedByPublishDate = [
            '/markdown_only' => null,
            '/no_sorting_prefix' => null,
            '/twig_only' => null,
            '/with_children' => null,
            '/yaml_and_markdown_and_twig' => null,
            '/yaml_and_twig' => null,
            '/yaml_only' => null,
            '/not_published/child1' => '2016-01-01 00:00:00.000000',
            '/yaml_and_twig/child1' => '2016-01-01 00:00:00.000000',
            '/yaml_and_twig/child2' => '2016-01-02 00:00:00.000000',
            '/with_children/_child1' => '2016-01-03 00:00:00.000000',
            '/with_children/_child2' => '2016-01-04 00:00:00.000000',
        ];
        $pagesSortedByPath = [
            '/markdown_only' => '/markdown_only',
            '/no_sorting_prefix' => '/no_sorting_prefix',
            '/not_published/child1' => '/not_published/child1',
            '/twig_only' => '/twig_only',
            '/with_children' => '/with_children',
            '/with_children/_child1' => '/with_children/_child1',
            '/with_children/_child2' => '/with_children/_child2',
            '/yaml_and_markdown_and_twig' => '/yaml_and_markdown_and_twig',
            '/yaml_and_twig' => '/yaml_and_twig',
            '/yaml_and_twig/child1' => '/yaml_and_twig/child1',
            '/yaml_and_twig/child2' => '/yaml_and_twig/child2',
            '/yaml_only' => '/yaml_only',
        ];
        $pagesSortedByFilesystemPath = [
            '/yaml_only' => '/01.yaml_only',
            '/markdown_only' => '/02.markdown_only',
            '/yaml_and_markdown_and_twig' => '/03.yaml_and_markdown_and_twig',
            '/with_children' => '/04.with_children',
            '/with_children/_child1' => '/04.with_children/_child1',
            '/with_children/_child2' => '/04.with_children/_child2',
            '/twig_only' => '/05.twig_only',
            '/yaml_and_twig' => '/06.yaml_and_twig',
            '/yaml_and_twig/child1' => '/06.yaml_and_twig/01.child1',
            '/yaml_and_twig/child2' => '/06.yaml_and_twig/02.child2',
            '/not_published/child1' => '/not_published/01.child1',
            '/no_sorting_prefix' => '/no_sorting_prefix',
        ];
        $pagesSortedByCustomFunction = [
            '/yaml_and_twig/child1' => 'Child1',
            '/with_children/_child1' => 'Child1',
            '/not_published/child1' => 'Child1',
            '/with_children/_child2' => 'Child2',
            '/yaml_and_twig/child2' => 'Child2',
            '/markdown_only' => 'Markdown Only',
            '/no_sorting_prefix' => 'No Sorting Prefix',
            '/twig_only' => 'Twig Only',
            '/with_children' => 'With Children',
            '/yaml_and_markdown_and_twig' => 'Yaml And Markdown',
            '/yaml_and_twig' => 'Yaml And Twig',
            '/yaml_only' => 'testtitle',
        ];
        $pagesSortedByExtraValue = [
            '/markdown_only' => '',
            '/no_sorting_prefix' => '',
            '/not_published/child1' => '',
            '/twig_only' => '',
            '/with_children/_child1' => '',
            '/with_children/_child2' => '',
            '/yaml_and_twig/child1' => '',
            '/yaml_and_twig/child2' => '',
            '/yaml_and_markdown_and_twig' => '1449769188',
            '/yaml_only' => '1483272600',
            '/with_children' => '2017-01-01 12:10:00',
            '/yaml_and_twig' => 'invalid date value',
        ];

        $customFunction = fn (Page $a, Page $b) => strcmp($a->getTitle(), $b->getTitle());
        yield SortableIterator::SORT_BY_NAME => [SortableIterator::SORT_BY_NAME, $pagesSortedByName];
        yield SortableIterator::SORT_BY_SLUG => [SortableIterator::SORT_BY_SLUG, $pagesSortedBySlug];
        yield SortableIterator::SORT_BY_TITLE => [SortableIterator::SORT_BY_TITLE, $pagesSortedByTitle];
        yield SortableIterator::SORT_BY_DATE => [SortableIterator::SORT_BY_DATE, $pagesSortedByDate];
        yield SortableIterator::SORT_BY_PUBLISH_DATE => [SortableIterator::SORT_BY_PUBLISH_DATE, $pagesSortedByPublishDate];
        yield SortableIterator::SORT_BY_PATH => [SortableIterator::SORT_BY_PATH, $pagesSortedByPath];
        yield SortableIterator::SORT_BY_FILESYSTEM_PATH => [SortableIterator::SORT_BY_FILESYSTEM_PATH, $pagesSortedByFilesystemPath];
        yield SortableIterator::SORT_BY_EXTRA_VALUE => [[SortableIterator::SORT_BY_EXTRA_VALUE, 'my_custom_date'], $pagesSortedByExtraValue];
        yield 'custom callback' => [$customFunction, $pagesSortedByCustomFunction];
    }

    /**
     * @test
     */
    public function invalidSortMethodThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new SortableIterator(new ArrayIterator([]), 'invalid method');
    }

    /**
     * @return PageFinder
     */
    private function getFinder()
    {
        return clone $this->finderPrototype;
    }
}
