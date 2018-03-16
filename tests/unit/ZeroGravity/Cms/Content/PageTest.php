<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Symfony\Component\Finder\SplFileInfo;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Meta\Metadata;
use ZeroGravity\Cms\Content\Page;

class PageTest extends BaseUnit
{
    public function _before()
    {
    }

    public function _after()
    {
    }

    /**
     * @test
     */
    public function filesystemPathContainsParentPath()
    {
        $parentPage = new Page('the_parent', [], null);
        $childPage = new Page('the_child', [], $parentPage);
        $childChildPage = new Page('one_more_child', [], $childPage);

        $this->assertSame('/the_parent/the_child', $childPage->getFilesystemPath()->toString());
        $this->assertSame('/the_parent/the_child/one_more_child', $childChildPage->getFilesystemPath()->toString());
    }

    /**
     * @test
     */
    public function pathContainsParentSlug()
    {
        $parentPage = new Page('the_parent', ['slug' => ''], null);
        $childPage = new Page('the_child', ['slug' => 'foo'], $parentPage);
        $childChildPage = new Page('one_more_child', ['slug' => 'bar'], $childPage);

        $this->assertSame('/', $parentPage->getPath()->toString());
        $this->assertSame('/foo', $childPage->getPath()->toString());
        $this->assertSame('/foo/bar', $childChildPage->getPath()->toString());
    }

    /**
     * @test
     */
    public function fileAliasesAreApplied()
    {
        $page = new Page('page', [
            'file_aliases' => [
                'my_alias' => 'some-image.jpg',
            ],
        ]);

        $page->setFiles($this->createFiles([
            FileTypeDetector::TYPE_IMAGE => [
                'some-image.jpg' => '/some-image.jpg',
                'some-other-image.jpg' => '/some-other-image.jpg',
            ],
        ]));

        $this->assertArrayHasKey('my_alias', $page->getFiles());
        $this->assertArrayHasKey('some-image.jpg', $page->getFiles());
        $this->assertSame($page->getFile('some-image.jpg'), $page->getFile('my_alias'));
    }

    /**
     * @test
     */
    public function filesCanBeFetchedByType()
    {
        $page = new Page('page');

        $page->setFiles($this->createFiles([
            FileTypeDetector::TYPE_IMAGE => [
                'some-image.jpg' => '/some-image.jpg',
                'some-other-image.jpg' => '/some-other-image.jpg',
            ],
            FileTypeDetector::TYPE_DOCUMENT => [
                'foo.pdf' => '/foo.pdf',
            ],
            FileTypeDetector::TYPE_MARKDOWN => [
                'page.md' => '/page.md',
            ],
            FileTypeDetector::TYPE_YAML => [
                'page.yaml' => '/page.yaml',
            ],
            FileTypeDetector::TYPE_TWIG => [
                'page.html.twig' => '/page.html.twig',
            ],
        ]));

        $this->assertSame(
            [
                'some-image.jpg',
                'some-other-image.jpg',
            ],
            array_keys($page->getImages())
        );
        $this->assertSame(
            [
                'foo.pdf',
            ],
            array_keys($page->getDocuments())
        );
        $this->assertSame('/page.md', $page->getMarkdownFile()->getPathname());
        $this->assertSame('/page.yaml', $page->getYamlFile()->getPathname());
        $this->assertSame('/page.html.twig', $page->getTwigFile()->getPathname());
    }

    /**
     * @test
     */
    public function filesByTypeReturnEmptyDefaults()
    {
        $page = new Page('page');

        $this->assertEmpty($page->getImages());
        $this->assertEmpty($page->getDocuments());
        $this->assertNull($page->getMarkdownFile());
        $this->assertNull($page->getYamlFile());
        $this->assertNull($page->getTwigFile());
    }

    /**
     * @test
     */
    public function contentCanBeSetAndNullified()
    {
        $page = new Page('page');

        $this->assertNull($page->getContent());
        $page->setContent('This is the content');
        $this->assertSame('This is the content', $page->getContent());
        $page->setContent(null);
        $this->assertNull($page->getContent());
    }

    /**
     * @test
     */
    public function parentCanBeFetched()
    {
        $parentPage = new Page('the_parent', [], null);
        $childPage = new Page('the_child', [], $parentPage);

        $this->assertSame($parentPage, $childPage->getParent());
    }

    /**
     * @test
     */
    public function settingsCanBeFetchedExplicitly()
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

        $this->assertSame('Page title', $page->getTitle());
        $this->assertTrue($page->isVisible());
        $this->assertTrue($page->isModular());
        $this->assertSame('main.html.twig', $page->getLayoutTemplate());
        $this->assertSame('render_with_h1.html.twig', $page->getContentTemplate());
        $this->assertSame('CustomBundle:Custom:action', $page->getController());
        $this->assertSame('custom_id', $page->getMenuId());
        $this->assertSame('custom label', $page->getMenuLabel());
        $this->assertSame(['fancy_extra_settings' => 'are not validated'], $page->getExtraValues());
    }

    /**
     * @test
     */
    public function childrenCanBeAssignedAndFetched()
    {
        $parent = new Page('page');
        $child1 = new Page('child1', [], $parent);
        $child2 = new Page('child2', [], $parent);

        $this->assertSame([
            '/page/child1' => $child1,
            '/page/child2' => $child2,
        ], $parent->getChildren()->toArray());
    }

    /**
     * @test
     */
    public function extraValuesCanBeFetched()
    {
        $page = new Page('page', [
            'extra' => [
                'fancy_extra_settings' => 'are not validated',
            ],
        ]);

        $this->assertSame('are not validated', $page->getExtra('fancy_extra_settings'));
        $this->assertNull($page->getExtra('does_not_exist'));
        $this->assertSame('default', $page->getExtra('does_not_exist', 'default'));
    }

    /**
     * @test
     */
    public function publishDateIsCastToDateTimeImmutable()
    {
        $page = new Page('page');
        $this->assertNull($page->getPublishDate());

        $page = new Page('page', ['publish_date' => new \DateTime()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getPublishDate());

        $page = new Page('page', ['publish_date' => new \DateTimeImmutable()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getPublishDate());

        $page = new Page('page', ['publish_date' => '2017-01-01 12:00:00']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getPublishDate());
    }

    /**
     * @test
     */
    public function unpublishDateIsCastToDateTimeImmutable()
    {
        $page = new Page('page');
        $this->assertNull($page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => new \DateTime()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => new \DateTimeImmutable()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getUnpublishDate());

        $page = new Page('page', ['unpublish_date' => '2017-01-01 12:00:00']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getUnpublishDate());
    }

    /**
     * @test
     */
    public function dateIsCastToDateTimeImmutable()
    {
        $page = new Page('page');
        $this->assertNull($page->getDate());

        $page = new Page('page', ['date' => new \DateTime()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getDate());

        $page = new Page('page', ['date' => new \DateTimeImmutable()]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getDate());

        $page = new Page('page', ['date' => '2017-01-01 12:00:00']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getDate());
    }

    /**
     * @test
     */
    public function pageIsPublishedByDefault()
    {
        $page = new Page('page');
        $this->assertTrue($page->isPublished());
    }

    /**
     * @test
     */
    public function publishingCanBeControlledByDates()
    {
        $page = new Page('page', [
            'publish_date' => new \DateTimeImmutable('-10 seconds'),
        ]);
        $this->assertTrue($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new \DateTimeImmutable('+10 seconds'),
        ]);
        $this->assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new \DateTimeImmutable('-10 seconds'),
            'unpublish_date' => new \DateTimeImmutable('+10 seconds'),
        ]);
        $this->assertTrue($page->isPublished());

        $page = new Page('page', [
            'publish_date' => new \DateTimeImmutable('-10 seconds'),
            'unpublish_date' => new \DateTimeImmutable('-5 seconds'),
        ]);
        $this->assertFalse($page->isPublished());

        $page = new Page('page', [
            'unpublish_date' => new \DateTimeImmutable('-5 seconds'),
        ]);
        $this->assertFalse($page->isPublished());
    }

    /**
     * @test
     */
    public function pageCanBeUnpublishedAndDatesAreIgnored()
    {
        $page = new Page('page', [
            'publish' => false,
        ]);
        $this->assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish' => false,
            'publish_date' => new \DateTimeImmutable('-10 seconds'),
        ]);
        $this->assertFalse($page->isPublished());

        $page = new Page('page', [
            'publish' => false,
            'unpublish_date' => new \DateTimeImmutable('+10 seconds'),
        ]);
        $this->assertFalse($page->isPublished());
    }

    /**
     * @test
     */
    public function defaultTitleIsGeneratedBasedOnName()
    {
        $page = new Page('page');
        $this->assertSame('Page', $page->getTitle());

        $page = new Page('name-with_dashes_and_underscores');
        $this->assertSame('Name With Dashes And Underscores', $page->getTitle());
    }

    /**
     * @test
     */
    public function taxonomyIsNormalizedToArrays()
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
                'category' => 'baz',
            ],
        ]);

        $this->assertSame([
            'tag' => ['foo', 'bar'],
            'category' => ['baz'],
        ], $page->getTaxonomies());

        $page = new Page('page', [
            'taxonomy' => null,
        ]);

        $this->assertSame([], $page->getTaxonomies());
    }

    /**
     * @test
     */
    public function taxonomyGetterDefaultsEmpty()
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
            ],
        ]);

        $this->assertSame(['foo', 'bar'], $page->getTaxonomy('tag'));
        $this->assertSame([], $page->getTaxonomy('category'));
    }

    /**
     * @test
     */
    public function taxonomyProvidesQuickGetters()
    {
        $page = new Page('page', [
            'taxonomy' => [
                'tag' => ['foo', 'bar'],
                'category' => 'baz',
                'author' => ['David', 'Julian'],
            ],
        ]);

        $this->assertSame(['foo', 'bar'], $page->getTags());
        $this->assertSame(['baz'], $page->getCategories());
        $this->assertSame(['David', 'Julian'], $page->getAuthors());
    }

    /**
     * @test
     */
    public function contentTypeDefaultsToPage()
    {
        $page = new Page('page', [
        ]);

        $this->assertSame('page', $page->getContentType());
    }

    /**
     * @test
     */
    public function contentTypeCanBeSet()
    {
        $page = new Page('page', [
            'content_type' => 'custom-type',
        ]);

        $this->assertSame('custom-type', $page->getContentType());
    }

    /**
     * @param array $fileNamesByType
     *
     * @return File[]
     */
    private function createFiles(array $fileNamesByType)
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
