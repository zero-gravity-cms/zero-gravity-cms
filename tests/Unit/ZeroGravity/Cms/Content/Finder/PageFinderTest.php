<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use InvalidArgumentException;
use Iterator;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilter;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

#[Group('finder')]
class PageFinderTest extends BaseUnit
{
    private ?PageFinder $finderPrototype = null;

    protected function _before(): void
    {
        $mapper = $this->getValidPagesFilesystemMapper();
        $repository = new ContentRepository($mapper, new ArrayAdapter(), false);

        $this->finderPrototype = $repository->getPageFinder();
    }

    #[Test]
    public function cannotIterateOverEmptyPageFinder(): void
    {
        $finder = new PageFinder();
        $this->expectException(LogicException::class);
        count($finder);
    }

    #[DataProvider('provideFinderMethods')]
    #[Test]
    public function finderMethodReturnsThisForChaining(string $method, string|int|true $param): void
    {
        $finder = $this->getFinder();
        $returnValue = $finder->$method($param, null);

        self::assertSame($finder, $returnValue);
    }

    public static function provideFinderMethods(): Iterator
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

    #[Test]
    public function basicPageFinderReturnsAllPublishedPages(): void
    {
        $finder = $this->getFinder();
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByPublishState(): void
    {
        $finder = $this->getFinder()
            ->published(true)
        ;
        self::assertCount(12, $finder);
        $finder = $this->getFinder()
            ->published(false)
        ;
        self::assertCount(1, $finder);
        $finder = $this->getFinder()
            ->published(null)
        ;
        self::assertCount(13, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByModularState(): void
    {
        $finder = $this->getFinder()
            ->modular(true)
        ;
        self::assertCount(1, $finder);

        $finder = $this->getFinder()
            ->modular(false)
        ;
        self::assertCount(11, $finder);

        $finder = $this->getFinder()
            ->modular(null)
        ;
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByModuleState(): void
    {
        $finder = $this->getFinder()
            ->module(true)
        ;
        self::assertCount(2, $finder);

        $finder = $this->getFinder()
            ->module(false)
        ;
        self::assertCount(10, $finder);

        $finder = $this->getFinder()
            ->module(null)
        ;
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByVisibleState(): void
    {
        $finder = $this->getFinder()
            ->visible(true)
        ;
        self::assertCount(9, $finder);

        $finder = $this->getFinder()
            ->visible(false)
        ;
        self::assertCount(3, $finder);

        $finder = $this->getFinder()
            ->visible(null)
        ;
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByName(): void
    {
        $finder = $this->getFinder()
            ->name('04.with_children')
        ;
        self::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notName('04.with_children')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->name('0?.child?')
        ;
        self::assertCount(3, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notName('0?.child?')
        ;
        self::assertCount(9, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->name('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison');
        $finder = $this->getFinder()
            ->notName('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison, negated');
    }

    #[Test]
    public function pagesCanBeFilteredBySlug(): void
    {
        $finder = $this->getFinder()
            ->slug('yaml_and_twig')
        ;
        self::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notSlug('yaml_and_twig')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->slug('child?')
        ;
        self::assertCount(3, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notSlug('child?')
        ;
        self::assertCount(9, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->slug('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison');
        $finder = $this->getFinder()
            ->notSlug('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison, negated');
    }

    #[Test]
    public function pagesCanBeFilteredByTitle(): void
    {
        $finder = $this->getFinder()
            ->title('Yaml And Twig')
        ;
        self::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notTitle('Yaml And Twig')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->title('testtitle')
        ;
        self::assertCount(1, $finder, 'String comparison, custom title');

        $finder = $this->getFinder()
            ->title('Child?')
        ;
        self::assertCount(5, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->title('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison');
    }

    #[Test]
    public function pagesCanBeFilteredByContentType(): void
    {
        $finder = $this->getFinder()
            ->contentType('page')
        ;
        self::assertCount(10, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notContentType('page')
        ;
        self::assertCount(2, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->contentType('custom-child')
        ;
        self::assertCount(2, $finder, 'String comparison, custom contentType');

        $finder = $this->getFinder()
            ->contentType('custom*')
        ;
        self::assertCount(2, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->contentType('/.*tom-ch.*/i')
        ;
        self::assertCount(2, $finder, 'Regex comparison');
    }

    #[Test]
    public function pagesCanBeFilteredByDepth(): void
    {
        $finder = $this->getFinder()
            ->depth('0')
        ;
        self::assertCount(7, $finder, 'Depth 0');

        $finder = $this->getFinder()
            ->depth('> 0')
        ;
        self::assertCount(5, $finder, 'Depth > 0');

        $finder = $this->getFinder()
            ->depth('>= 0')
        ;
        self::assertCount(12, $finder, 'Depth >= 0');

        $finder = $this->getFinder()
            ->depth('< 1')
        ;
        self::assertCount(7, $finder, 'Depth > 0');

        $finder = $this->getFinder()
            ->depth('<= 1')
        ;
        self::assertCount(12, $finder, 'Depth >= 0');

        $finder = $this->getFinder()
            ->depth('1')
        ;
        self::assertCount(5, $finder, 'Depth 1');

        $finder = $this->getFinder()
            ->depth('2')
        ;
        self::assertCount(0, $finder, 'Depth 2');
    }

    #[Test]
    public function pagesCanBeFilteredByPath(): void
    {
        $finder = $this->getFinder()
            ->path('/with_children/_child1')
        ;
        self::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notPath('/with_children/_child1')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->path('/with_children/_child?')
        ;
        self::assertCount(2, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notPath('/with_children/_child?')
        ;
        self::assertCount(10, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->path('/*children/_child1')
        ;
        self::assertCount(1, $finder, 'Glob comparison with leading wildcard *');

        $finder = $this->getFinder()
            ->path('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison');
    }

    #[Test]
    public function pagesCanBeFilteredByFilesystemPath(): void
    {
        $finder = $this->getFinder()
            ->filesystemPath('/04.with_children/_child1')
        ;
        self::assertCount(1, $finder, 'String comparison');
        $finder = $this->getFinder()
            ->notFilesystemPath('/04.with_children/_child1')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->filesystemPath('/04.with_children/_child?')
        ;
        self::assertCount(2, $finder, 'Glob comparison');
        $finder = $this->getFinder()
            ->notFilesystemPath('/04.with_children/_child?')
        ;
        self::assertCount(10, $finder, 'Glob comparison, negated');

        $finder = $this->getFinder()
            ->filesystemPath('/*children/_child1')
        ;
        self::assertCount(1, $finder, 'Glob comparison with leading wildcard *');

        $finder = $this->getFinder()
            ->filesystemPath('/.*Chil.*/i')
        ;
        self::assertCount(6, $finder, 'Regex comparison');
    }

    #[Test]
    public function pagesCanBeFilteredByDate(): void
    {
        $finder = $this->getFinder()
            ->date('> 2016-12-31')
        ;
        self::assertCount(1, $finder);

        $finder = $this->getFinder()
            ->date('< 2017-01-02')
        ;
        self::assertCount(3, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByCustomCallback(): void
    {
        $finder = $this->getFinder()
            ->filter(static fn (Page $page): bool => 'yaml_and_twig' === $page->getSlug())
        ;
        self::assertCount(1, $finder);
    }

    #[Test]
    public function pagesCanBeFilteredByContent(): void
    {
        $finder = $this->getFinder()
            ->contains('This is the content of page 02.')
        ;
        self::assertCount(1, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->notContains('This is the content of page 02.')
        ;
        self::assertCount(11, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->contains('*page 02*')
        ;
        self::assertCount(1, $finder, 'Glob comparison');
    }

    #[Test]
    public function pagesCanBeFilteredByTags(): void
    {
        $finder = $this->getFinder()
            ->tag('tag1')
        ;
        self::assertCount(3, $finder, 'Single tag');

        $finder = $this->getFinder()
            ->tag(['tag1', 'tag2'])
        ;
        self::assertCount(1, $finder, 'Multiple tags');

        $finder = $this->getFinder()
            ->tag(['tag1', 'tag2'], TaxonomyTester::OPERATOR_OR)
        ;
        self::assertCount(4, $finder, 'Multiple tags, OR');

        $finder = $this->getFinder()
            ->notTag('tag1')
        ;
        self::assertCount(9, $finder, 'Single tag, negated');

        $finder = $this->getFinder()
            ->notTag(['tag1', 'tag2'])
        ;
        self::assertCount(11, $finder, 'Multiple tags, negated');

        $finder = $this->getFinder()
            ->notTag(['tag1', 'tag2'], TaxonomyTester::OPERATOR_OR)
        ;
        self::assertCount(8, $finder, 'Multiple tags, OR, negated');
    }

    #[Test]
    public function pagesCanBeFilteredByCategories(): void
    {
        $finder = $this->getFinder()
            ->category(['category1', 'category2'])
        ;
        self::assertCount(1, $finder, 'Multiple categories');

        $finder = $this->getFinder()
            ->notCategory(['category1', 'category2'])
        ;
        self::assertCount(11, $finder, 'Multiple categories, negated');
    }

    #[Test]
    public function pagesCanBeFilteredByAuthors(): void
    {
        $finder = $this->getFinder()
            ->author('mary')
        ;
        self::assertCount(2, $finder, 'Single author');

        $finder = $this->getFinder()
            ->author(['john', 'mary'])
        ;
        self::assertCount(1, $finder, 'Multiple authors');

        $finder = $this->getFinder()
            ->notAuthor(['john', 'mary'], TaxonomyTester::OPERATOR_OR)
        ;
        self::assertCount(9, $finder, 'Multiple authors, OR, negated');
    }

    #[Test]
    public function pagesCanBeFilteredByNumFiles(): void
    {
        $finder = $this->getFinder()
            ->numFiles('> 0')
        ;
        self::assertCount(12, $finder, 'More than 0 files');

        $finder = $this->getFinder()
            ->numFiles('> 1')
        ;
        self::assertCount(6, $finder, 'More than 1 file');
    }

    #[Test]
    public function pagesCanBeFilteredByNumImages(): void
    {
        $finder = $this->getFinder()
            ->numImages('>= 1')
        ;
        self::assertCount(3, $finder, 'At least 1 image');
    }

    #[Test]
    public function pagesCanBeFilteredByNumDocuments(): void
    {
        $finder = $this->getFinder()
            ->numDocuments('> 0')
        ;
        self::assertCount(2, $finder, 'More than 0 documents');
    }

    #[Test]
    public function pagesCanBeFilteredByExtra(): void
    {
        $finder = $this->getFinder()
            ->extra('custom', 'custom_value')
        ;
        self::assertCount(3, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->notExtra('custom', 'custom_value')
        ;
        self::assertCount(9, $finder, 'String comparison, negated');

        $finder = $this->getFinder()
            ->extra('custom', '> aaa')
        ;
        self::assertCount(4, $finder, 'String comparison, comparator');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 2016-01-01')
        ;
        self::assertCount(2, $finder, 'String comparison of date value');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 2016-01-01', ExtraFilter::COMPARATOR_DATE)
        ;
        self::assertCount(2, $finder, 'Date comparison of date value');

        $finder = $this->getFinder()
            ->extra('my_custom_date', '> 1449769188', ExtraFilter::COMPARATOR_NUMERIC)
        ;
        self::assertCount(1, $finder, 'Numeric comparison of date value');
    }

    #[Test]
    public function stringComparatorThrowsExceptionForInvalidPattern(): void
    {
        $finder = $this->getFinder()
            ->extra('custom', '')
        ;
        $this->expectException(InvalidArgumentException::class);
        $finder->count();
    }

    #[Test]
    public function extraFilterThrowsExceptionForInvalidComparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getFinder()
            ->extra('custom', 'somevalue', 'this-is-not-a-comparator')
        ;
    }

    #[Test]
    public function pagesCanBeFilteredBySetting(): void
    {
        $finder = $this->getFinder()
            ->setting('visible', true)
        ;
        self::assertCount(9, $finder);

        $finder = $this->getFinder()
            ->notSetting('visible', true)
        ;
        self::assertCount(3, $finder);

        $finder = $this->getFinder()
            ->setting('menu_id', 'zero-gravity')
        ;
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pageResultsCanBeLimited(): void
    {
        $finder = $this->getFinder()
            ->limit(5)
        ;
        self::assertCount(5, $finder);

        $finder = $this->getFinder()
            ->limit(50)
        ;
        self::assertCount(12, $finder);
    }

    #[Test]
    public function pageResultsCanBeOffset(): void
    {
        $finder = $this->getFinder()
            ->offset(5)
        ;
        self::assertCount(7, $finder);
    }

    #[Test]
    public function findersCanBeAppended(): void
    {
        $finder = $this->getFinder();
        $finder->append($this->getFinder());
        self::assertCount(24, $finder);

        $finder = PageFinder::create();
        $finder
            ->append(new Page('single page'))
            ->append([
                new Page('page2'),
                new Page('page3'),
            ])
        ;
        self::assertCount(3, $finder);

        $finder = $this->getFinder();
        $finder->append($this->getFinder()->getIterator());
        self::assertCount(24, $finder);
    }

    private function getFinder(): ?PageFinder
    {
        return clone $this->finderPrototype;
    }
}
