<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder;

use InvalidArgumentException;
use Iterator;
use LogicException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilter;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

/**
 * @group finder
 */
class PageFinderTest extends BaseUnit
{
    private ?PageFinder $finderPrototype = null;

    public function _before()
    {
        $mapper = $this->getValidPagesFilesystemMapper();
        $repository = new ContentRepository($mapper, new ArrayAdapter(), false);

        $this->finderPrototype = $repository->getPageFinder();
    }

    /**
     * @test
     */
    public function cannotIterateOverEmptyPageFinder()
    {
        $finder = new PageFinder();
        $this->expectException(LogicException::class);
        count($finder);
    }

    /**
     * @test
     *
     * @dataProvider provideFinderMethods
     */
    public function finderMethodReturnsThisForChaining(string $method, $param)
    {
        $finder = $this->getFinder();
        $returnValue = $finder->$method($param, null);

        static::assertSame($finder, $returnValue);
    }

    public function provideFinderMethods(): Iterator
    {
        yield ['date', '> now'];
        yield ['name', ''];
        yield ['notName', ''];
        yield ['slug', ''];
        yield ['notSlug', ''];
        yield ['depth', 0];
        yield ['numFiles', 0];
        yield ['numImages', 0];
        yield ['numDocuments', 0];
        yield ['path', ''];
        yield ['notPath', ''];
        yield ['filesystemPath', ''];
        yield ['notFilesystemPath', ''];
        yield ['title', ''];
        yield ['notTitle', ''];
        yield ['contains', ''];
        yield ['notContains', ''];
        yield ['tag', ''];
        yield ['notTag', ''];
        yield ['category', ''];
        yield ['notCategory', ''];
        yield ['author', ''];
        yield ['notAuthor', ''];
        yield ['published', true];
        yield ['modular', true];
        yield ['module', true];
        yield ['visible', true];
        yield ['extra', ''];
        yield ['notExtra', ''];
        yield ['setting', ''];
        yield ['notSetting', ''];
        yield ['contentType', ''];
        yield ['notContentType', ''];
    }

    /**
     * @test
     */
    public function basicPageFinderReturnsAllPublishedPages()
    {
        $finder = $this->getFinder();
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByPublishState()
    {
        $finder = $this->getFinder()
            ->published(true)
        ;
        static::assertCount(12, $finder);
        $finder = $this->getFinder()
            ->published(false)
        ;
        static::assertCount(1, $finder);
        $finder = $this->getFinder()
            ->published(null)
        ;
        static::assertCount(13, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByModularState()
    {
        $finder = $this->getFinder()
            ->modular(true)
        ;
        static::assertCount(1, $finder);

        $finder = $this->getFinder()
            ->modular(false)
        ;
        static::assertCount(11, $finder);

        $finder = $this->getFinder()
            ->modular(null)
        ;
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByModuleState()
    {
        $finder = $this->getFinder()
            ->module(true)
        ;
        static::assertCount(2, $finder);

        $finder = $this->getFinder()
            ->module(false)
        ;
        static::assertCount(10, $finder);

        $finder = $this->getFinder()
            ->module(null)
        ;
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByVisibleState()
    {
        $finder = $this->getFinder()
            ->visible(true)
        ;
        static::assertCount(9, $finder);

        $finder = $this->getFinder()
            ->visible(false)
        ;
        static::assertCount(3, $finder);

        $finder = $this->getFinder()
            ->visible(null)
        ;
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByName()
    {
        $finder = $this->getFinder()
            ->name('04.with_children')
        ;
        static::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notName('04.with_children')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->name('0?.child?')
        ;
        static::assertCount(3, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notName('0?.child?')
        ;
        static::assertCount(9, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->name('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison');
        $finder = $this->getFinder()
            ->notName('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison, negated');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredBySlug()
    {
        $finder = $this->getFinder()
            ->slug('yaml_and_twig')
        ;
        static::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notSlug('yaml_and_twig')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->slug('child?')
        ;
        static::assertCount(3, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notSlug('child?')
        ;
        static::assertCount(9, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->slug('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison');
        $finder = $this->getFinder()
            ->notSlug('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison, negated');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByTitle()
    {
        $finder = $this->getFinder()
            ->title('Yaml And Twig')
        ;
        static::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notTitle('Yaml And Twig')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->title('testtitle')
        ;
        static::assertCount(1, $finder, 'String comparison, custom title');

        $finder = $this->getFinder()
            ->title('Child?')
        ;
        static::assertCount(5, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->title('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByContentType()
    {
        $finder = $this->getFinder()
            ->contentType('page')
        ;
        static::assertCount(10, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notContentType('page')
        ;
        static::assertCount(2, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->contentType('custom-child')
        ;
        static::assertCount(2, $finder, 'String comparison, custom contentType');

        $finder = $this->getFinder()
            ->contentType('custom*')
        ;
        static::assertCount(2, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->contentType('/.*tom-ch.*/i')
        ;
        static::assertCount(2, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByDepth()
    {
        $finder = $this->getFinder()
            ->depth(0)
        ;
        static::assertCount(7, $finder, 'Depth 0');

        $finder = $this->getFinder()
            ->depth('> 0')
        ;
        static::assertCount(5, $finder, 'Depth > 0');

        $finder = $this->getFinder()
            ->depth('>= 0')
        ;
        static::assertCount(12, $finder, 'Depth >= 0');

        $finder = $this->getFinder()
            ->depth('< 1')
        ;
        static::assertCount(7, $finder, 'Depth > 0');

        $finder = $this->getFinder()
            ->depth('<= 1')
        ;
        static::assertCount(12, $finder, 'Depth >= 0');

        $finder = $this->getFinder()
            ->depth(1)
        ;
        static::assertCount(5, $finder, 'Depth 1');

        $finder = $this->getFinder()
            ->depth(2)
        ;
        static::assertCount(0, $finder, 'Depth 2');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByPath()
    {
        $finder = $this->getFinder()
            ->path('/with_children/_child1')
        ;
        static::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notPath('/with_children/_child1')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->path('/with_children/_child?')
        ;
        static::assertCount(2, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notPath('/with_children/_child?')
        ;
        static::assertCount(10, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->path('/*children/_child1')
        ;
        static::assertCount(1, $finder, 'Glob comparison with leading wildcard *');

        $finder = $this->getFinder()
            ->path('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByFilesystemPath()
    {
        $finder = $this->getFinder()
            ->filesystemPath('/04.with_children/_child1')
        ;
        static::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notFilesystemPath('/04.with_children/_child1')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->filesystemPath('/04.with_children/_child?')
        ;
        static::assertCount(2, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notFilesystemPath('/04.with_children/_child?')
        ;
        static::assertCount(10, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->filesystemPath('/*children/_child1')
        ;
        static::assertCount(1, $finder, 'Glob comparison with leading wildcard *');

        $finder = $this->getFinder()
            ->filesystemPath('/.*Chil.*/i')
        ;
        static::assertCount(6, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByDate()
    {
        $finder = $this->getFinder()
            ->date('> 2016-12-31')
        ;
        static::assertCount(1, $finder);

        $finder = $this->getFinder()
            ->date('< 2017-01-02')
        ;
        static::assertCount(3, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByCustomCallback()
    {
        $finder = $this->getFinder()
            ->filter(fn (Page $page) => 'yaml_and_twig' === $page->getSlug())
        ;
        static::assertCount(1, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByContent()
    {
        $finder = $this->getFinder()
            ->contains('This is the content of page 02.')
        ;
        static::assertCount(1, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->notContains('This is the content of page 02.')
        ;
        static::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->contains('*page 02*')
        ;
        static::assertCount(1, $finder, 'Glob comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByTags()
    {
        $finder = $this->getFinder()
            ->tag('tag1')
        ;
        static::assertCount(3, $finder, 'Single tag');

        $finder = $this->getFinder()
            ->tag(['tag1', 'tag2'])
        ;
        static::assertCount(1, $finder, 'Multiple tags');

        $finder = $this->getFinder()
            ->tag(['tag1', 'tag2'], TaxonomyTester::OPERATOR_OR)
        ;
        static::assertCount(4, $finder, 'Multiple tags, OR');

        $finder = $this->getFinder()
            ->notTag('tag1')
        ;
        static::assertCount(9, $finder, 'Single tag, negated');

        $finder = $this->getFinder()
            ->notTag(['tag1', 'tag2'])
        ;
        static::assertCount(11, $finder, 'Multiple tags, negated');

        $finder = $this->getFinder()
            ->notTag(['tag1', 'tag2'], TaxonomyTester::OPERATOR_OR)
        ;
        static::assertCount(8, $finder, 'Multiple tags, OR, negated');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByCategories()
    {
        $finder = $this->getFinder()
            ->category(['category1', 'category2'])
        ;
        static::assertCount(1, $finder, 'Multiple categories');

        $finder = $this->getFinder()
            ->notCategory(['category1', 'category2'])
        ;
        static::assertCount(11, $finder, 'Multiple categories, negated');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByAuthors()
    {
        $finder = $this->getFinder()
            ->author('mary')
        ;
        static::assertCount(2, $finder, 'Single author');

        $finder = $this->getFinder()
            ->author(['john', 'mary'])
        ;
        static::assertCount(1, $finder, 'Multiple authors');

        $finder = $this->getFinder()
            ->notAuthor(['john', 'mary'], TaxonomyTester::OPERATOR_OR)
        ;
        static::assertCount(9, $finder, 'Multiple authors, OR, negated');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByNumFiles()
    {
        $finder = $this->getFinder()
            ->numFiles('> 0')
        ;
        static::assertCount(12, $finder, 'More than 0 files');

        $finder = $this->getFinder()
            ->numFiles('> 1')
        ;
        static::assertCount(6, $finder, 'More than 1 file');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByNumImages()
    {
        $finder = $this->getFinder()
            ->numImages('>= 1')
        ;
        static::assertCount(3, $finder, 'At least 1 image');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByNumDocuments()
    {
        $finder = $this->getFinder()
            ->numDocuments('> 0')
        ;
        static::assertCount(2, $finder, 'More than 0 documents');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByExtra()
    {
        $finder = $this->getFinder()
            ->extra('custom', 'custom_value')
        ;
        static::assertCount(3, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->notExtra('custom', 'custom_value')
        ;
        static::assertCount(9, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->extra('custom', '> aaa')
        ;
        static::assertCount(4, $finder, 'String comparison, comparator');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 2016-01-01')
        ;
        static::assertCount(4, $finder, 'String comparison of date value, comparator');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 2016-01-01', ExtraFilter::COMPARATOR_DATE)
        ;
        static::assertCount(2, $finder, 'Date comparison of date value');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 1449769188', ExtraFilter::COMPARATOR_NUMERIC)
        ;
        static::assertCount(1, $finder, 'Numeric comparison of date value');
    }

    /**
     * @test
     */
    public function stringComparatorThrowsExceptionForInvalidPattern()
    {
        $finder = $this->getFinder()
            ->extra('custom', '')
        ;
        $this->expectException(InvalidArgumentException::class);
        $finder->count();
    }

    /**
     * @test
     */
    public function extraFilterThrowsExceptionForInvalidComparator()
    {
        $finder = $this->getFinder()
            ->extra('custom', 'somevalue', 'this-is-not-a-comparator')
        ;
        $this->expectException(InvalidArgumentException::class);
        $finder->count();
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredBySetting()
    {
        $finder = $this->getFinder()
            ->setting('visible', true)
        ;
        static::assertCount(9, $finder);

        $finder = $this->getFinder()
            ->notSetting('visible', true)
        ;
        static::assertCount(3, $finder);

        $finder = $this->getFinder()
            ->setting('menu_id', 'zero-gravity')
        ;
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pageResultsCanBeLimited()
    {
        $finder = $this->getFinder()
            ->limit(5)
        ;
        static::assertCount(5, $finder);

        $finder = $this->getFinder()
            ->limit(50)
        ;
        static::assertCount(12, $finder);
    }

    /**
     * @test
     */
    public function pageResultsCanBeOffset()
    {
        $finder = $this->getFinder()
            ->offset(5)
        ;
        static::assertCount(7, $finder);
    }

    /**
     * @test
     */
    public function findersCanBeAppended()
    {
        $finder = $this->getFinder();
        $finder->append($this->getFinder());
        static::assertCount(24, $finder);

        $finder = PageFinder::create();
        $finder
            ->append(new Page('single page'))
            ->append([
                new Page('page2'),
                new Page('page3'),
            ])
        ;
        static::assertCount(3, $finder);

        $finder = $this->getFinder();
        $finder->append($this->getFinder()->getIterator());
        static::assertCount(24, $finder);

        $this->expectException(InvalidArgumentException::class);
        $finder->append('string');
    }

    /**
     * @return PageFinder
     */
    private function getFinder()
    {
        return clone $this->finderPrototype;
    }
}
