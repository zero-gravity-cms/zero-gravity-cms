<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Codeception\Attribute\Group;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\SplFileInfo;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileTypes;
use ZeroGravity\Cms\Content\Meta\Metadata;
use ZeroGravity\Cms\Content\Page;

#[Group('page')]
class PageTest extends BaseUnit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    #[Test]
    public function filesystemPathContainsParentPath(): void
    {
        $parentPage = new Page('the_parent', [], null);
        $childPage = new Page('the_child', [], $parentPage);
        $childChildPage = new Page('one_more_child', [], $childPage);

        self::assertSame('/the_parent/the_child', $childPage->getFilesystemPath()->toString());
        self::assertSame('/the_parent/the_child/one_more_child', $childChildPage->getFilesystemPath()->toString());
    }

    #[Test]
    public function pathContainsParentSlug(): void
    {
        $parentPage = new Page('the_parent', ['slug' => ''], null);
        $childPage = new Page('the_child', ['slug' => 'foo'], $parentPage);
        $childChildPage = new Page('one_more_child', ['slug' => 'bar'], $childPage);

        self::assertSame('/', $parentPage->getPath()->toString());
        self::assertSame('/foo', $childPage->getPath()->toString());
        self::assertSame('/foo/bar', $childChildPage->getPath()->toString());
    }

    #[Test]
    public function fileAliasesAreApplied(): void
    {
        $page = new Page('page', [
            'file_aliases' => [
                'my_alias' => 'some-image.jpg',
            ],
        ]);

        $page->setFiles($this->createFiles([
            FileTypes::TYPE_IMAGE => [
                'some-image.jpg' => '/some-image.jpg',
                'some-other-image.jpg' => '/some-other-image.jpg',
            ],
        ]));

        self::assertArrayHasKey('my_alias', $page->getFiles());
        self::assertArrayHasKey('some-image.jpg', $page->getFiles());
        self::assertSame($page->getFile('some-image.jpg'), $page->getFile('my_alias'));
    }

    #[Test]
    public function filesCanBeFetchedByType(): void
    {
        $page = new Page('page');

        $page->setFiles($this->createFiles([
            FileTypes::TYPE_IMAGE => [
                'some-image.jpg' => '/some-image.jpg',
                'some-other-image.jpg' => '/some-other-image.jpg',
            ],
            FileTypes::TYPE_DOCUMENT => [
                'foo.pdf' => '/foo.pdf',
            ],
            FileTypes::TYPE_MARKDOWN => [
                'page.md' => '/page.md',
            ],
            FileTypes::TYPE_YAML => [
                'page.yaml' => '/page.yaml',
            ],
            FileTypes::TYPE_TWIG => [
                'page.html.twig' => '/page.html.twig',
            ],
        ]));

        self::assertSame([
            'some-image.jpg',
            'some-other-image.jpg',
        ], array_keys($page->getImages()));
        self::assertSame([
            'foo.pdf',
        ], array_keys($page->getDocuments()));
        self::assertSame('/page.md', $page->getMarkdownFile()->getPathname());
        self::assertSame('/page.yaml', $page->getYamlFile()->getPathname());
        self::assertSame('/page.html.twig', $page->getTwigFile()->getPathname());
    }

    #[Test]
    public function filesByTypeReturnEmptyDefaults(): void
    {
        $page = new Page('page');

        self::assertEmpty($page->getImages());
        self::assertEmpty($page->getDocuments());
        self::assertNull($page->getMarkdownFile());
        self::assertNull($page->getYamlFile());
        self::assertNull($page->getTwigFile());
    }

    #[Test]
    public function contentCanBeSetAndNullified(): void
    {
        $page = new Page('page');

        self::assertNull($page->getContent());
        $page->setContent('This is the content');
        self::assertSame('This is the content', $page->getContent());
        $page->setContent(null);
        self::assertNull($page->getContent());
    }

    #[Test]
    public function parentCanBeFetched(): void
    {
        $parentPage = new Page('the_parent', [], null);
        $childPage = new Page('the_child', [], $parentPage);

        self::assertSame($parentPage, $childPage->getParent());
    }

    #[Test]
    public function settingsCanBeFetchedExplicitly(): void
    {
        $page = new Page('page', [
            'slug' => 'page',
            'title' => 'Page title',
            'visible' => true,
            'modular' => true,
            'layout_template' => 'main.html.twig',
            'content_template' => 'render_with_h1.html.twig',
            'controller' => 'CustomBundle:Custom:action',
            'menu_id' => 'custom_id',
            'menu_label' => 'custom label',
            'extra' => [
                'fancy_extra_settings' => 'are not validated',
            ],
        ]);

        self::assertSame('Page title', $page->getTitle());
        self::assertTrue($page->isVisible());
        self::assertTrue($page->isModular());
        self::assertSame('main.html.twig', $page->getLayoutTemplate());
        self::assertSame('render_with_h1.html.twig', $page->getContentTemplate());
        self::assertSame('CustomBundle:Custom:action', $page->getController());
        self::assertSame('custom_id', $page->getMenuId());
        self::assertSame('custom label', $page->getMenuLabel());
        self::assertSame(['fancy_extra_settings' => 'are not validated'], $page->getExtraValues());
    }

    #[Test]
    public function childrenCanBeAssignedAndFetched(): void
    {
        $parent = new Page('page');
        $child1 = new Page('child1', [], $parent);
        $child2 = new Page('child2', [], $parent);

        self::assertSame([
            '/page/child1' => $child1,
            '/page/child2' => $child2,
        ], $parent->getChildren()->toArray());
    }

    #[Test]
    public function childrenAreRestrictedToOneLevelByDefault(): void
    {
        $parent = new Page('page');
        $child1 = new Page('child1', [], $parent);
        $child2 = new Page('child2', [], $parent);
        new Page('child3', [], $child2);

        self::assertSame([
            '/page/child1' => $child1,
            '/page/child2' => $child2,
        ], $parent->getChildren()->toArray());
    }

    #[Test]
    public function childrenFinderCanBeExtendedToDeeperLevel(): void
    {
        $parent = new Page('page');
        $child1 = new Page('child1', [], $parent);
        $child2 = new Page('child2', [], $parent);
        $child3 = new Page('child3', [], $child2);

        self::assertSame([
            '/page/child1' => $child1,
            '/page/child2' => $child2,
            '/page/child2/child3' => $child3,
        ], $parent->getChildren()->depth('< 2')->toArray());
    }

    #[Test]
    public function extraValuesCanBeFetched(): void
    {
        $page = new Page('page', [
            'extra' => [
                'fancy_extra_settings' => 'are not validated',
            ],
        ]);

        self::assertSame('are not validated', $page->getExtra('fancy_extra_settings'));
        self::assertNull($page->getExtra('does_not_exist'));
        self::assertSame('default', $page->getExtra('does_not_exist', 'default'));
    }

    #[Test]
    public function publishDateIsCastToDateTimeImmutable(): void
    {
        $page = new Page('page');
        self::assertNull($page->getPublishDate());

        $page = new Page('page', ['publish_date' => new DateTime()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getPublishDate());

        $page = new Page('page', ['publish_date' => new DateTimeImmutable()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getPublishDate());

        $page = new Page('page', ['publish_date' => '2017-01-01 12:00:00']);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getPublishDate());
    }

    #[Test]
    public function unpublishDateIsCastToDateTimeImmutable(): void
    {
        $page = new Page('page');
        self::assertNull($page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => new DateTime()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => new DateTimeImmutable()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => '2017-01-01 12:00:00']);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getUnpublishDate());
    }

    #[Test]
    public function dateIsCastToDateTimeImmutable(): void
    {
        $page = new Page('page');
        self::assertNull($page->getDate());

        $page = new Page('page', ['date' => new DateTime()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getDate());

        $page = new Page('page', ['date' => new DateTimeImmutable()]);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getDate());

        $page = new Page('page', ['date' => '2017-01-01 12:00:00']);
        self::assertInstanceOf(DateTimeImmutable::class, $page->getDate());
    }

    #[Test]
    public function pageIsPublishedByDefault(): void
    {
        $page = new Page('page');
        self::assertTrue($page->isPublished());
    }

    #[Test]
    public function publishingCanBeControlledByDates(): void
    {
        $page = new Page('page', [
            'publish_date' => new DateTimeImmutable('-10 seconds'),
        ]);
        self::assertTrue($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new DateTimeImmutable('+10 seconds'),
        ]);
        self::assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new DateTimeImmutable('-10 seconds'),
            'unpublish_date' => new DateTimeImmutable('+10 seconds'),
        ]);
        self::assertTrue($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new DateTimeImmutable('-10 seconds'),
            'unpublish_date' => new DateTimeImmutable('-5 seconds'),
        ]);
        self::assertFalse($page->isPublished());

        $page = new Page('page', [
            'unpublish_date' => new DateTimeImmutable('-5 seconds'),
        ]);
        self::assertFalse($page->isPublished());
    }

    #[Test]
    public function pageCanBeUnpublishedAndDatesAreIgnored(): void
    {
        $page = new Page('page', [
            'publish' => false,
        ]);
        self::assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish' => false,
            'publish_date' => new DateTimeImmutable('-10 seconds'),
        ]);
        self::assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish' => false,
            'unpublish_date' => new DateTimeImmutable('+10 seconds'),
        ]);
        self::assertFalse($page->isPublished());
    }

    #[Test]
    public function defaultTitleIsGeneratedBasedOnName(): void
    {
        $page = new Page('page');
        self::assertSame('Page', $page->getTitle());

        $page = new Page('name-with_dashes_and_underscores');
        self::assertSame('Name With Dashes And Underscores', $page->getTitle());
    }

    #[Test]
    public function taxonomyIsNormalizedToArrays(): void
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
                'category' => 'baz',
            ],
        ]);

        self::assertSame([
            'category' => ['baz'],
            'tag' => ['foo', 'bar'],
        ], $page->getTaxonomies());

        $page = new Page('page', [
            'taxonomy' => null,
        ]);

        self::assertSame([], $page->getTaxonomies());
    }

    #[Test]
    public function taxonomyGetterDefaultsEmpty(): void
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
            ],
        ]);

        self::assertSame(['foo', 'bar'], $page->getTaxonomy('tag'));
        self::assertSame([], $page->getTaxonomy('category'));
    }

    #[Test]
    public function taxonomyProvidesQuickGetters(): void
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
                'category' => 'baz',
                'author' => ['David', 'Julian'],
            ],
        ]);

        self::assertSame(['foo', 'bar'], $page->getTags());
        self::assertSame(['baz'], $page->getCategories());
        self::assertSame(['David', 'Julian'], $page->getAuthors());
    }

    #[Test]
    public function contentTypeDefaultsToPage(): void
    {
        $page = new Page('page', [
        ]);

        self::assertSame('page', $page->getContentType());
    }

    #[Test]
    public function contentTypeCanBeSet(): void
    {
        $page = new Page('page', [
            'content_type' => 'custom-type',
        ]);

        self::assertSame('custom-type', $page->getContentType());
    }

    /**
     * @return File[]
     */
    private function createFiles(array $fileNamesByType): array
    {
        $files = [];
        foreach ($fileNamesByType as $type => $fileNames) {
            foreach ($fileNames as $filename => $path) {
                $fileInfo = new SplFileInfo($path, $path, $path);
                $files[$filename] = new File($fileInfo, '', new Metadata([]), $type);
            }
        }

        return $files;
    }
}
