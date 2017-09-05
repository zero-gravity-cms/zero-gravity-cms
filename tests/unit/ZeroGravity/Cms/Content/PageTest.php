<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Metadata;
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

        $parentPage->validateSettings();
        $childPage->validateSettings();
        $childChildPage->validateSettings();

        $this->assertSame('/', $parentPage->getPath()->toString());
        $this->assertSame('/foo', $childPage->getPath()->toString());
        $this->assertSame('/foo/bar', $childChildPage->getPath()->toString());
    }

    /**
     * @test
     */
    public function slugIsRequired()
    {
        $page = new Page('page', [], null);

        $this->expectException(MissingOptionsException::class);
        $page->validateSettings();
    }

    /**
     * @test
     */
    public function fileAliasesAreApplied()
    {
        $page = new Page('page', [
            'slug' => 'page',
            'file_aliases' => [
                'my_alias' => 'some-image.jpg',
            ],
        ]);
        $page->validateSettings();

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
        $page = new Page('page', ['slug' => 'page']);
        $page->validateSettings();

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
        $page = new Page('page', ['slug' => 'page']);
        $page->validateSettings();

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
        $page = new Page('page', ['slug' => 'page']);

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
            'is_visible' => true,
            'is_modular' => true,
            'template' => 'main.html.twig',
            'controller' => 'CustomBundle:Custom:action',
            'menu_id' => 'custom_id',
            'menu_label' => 'custom label',
            'extra' => [
                'fancy_extra_settings' => 'are not validated',
            ],
        ]);
        $page->validateSettings();

        $this->assertSame('Page title', $page->getTitle());
        $this->assertTrue($page->isVisible());
        $this->assertTrue($page->isModular());
        $this->assertSame('main.html.twig', $page->getTemplate());
        $this->assertSame('CustomBundle:Custom:action', $page->getController());
        $this->assertSame('custom_id', $page->getMenuId());
        $this->assertSame('custom label', $page->getMenuLabel());
        $this->assertSame(['fancy_extra_settings' => 'are not validated'], $page->getExtra());
    }

    /**
     * @test
     */
    public function settingsAreDefaultedAndCanBeFetched()
    {
        $page = new Page('page', ['slug' => 'page']);
        $page->validateSettings();

        $expectedSettings = [
            'slug' => 'page',
            'title' => null,
            'is_visible' => false,
            'is_modular' => false,
            'template' => null,
            'controller' => null,
            'menu_id' => 'default',
            'menu_label' => null,
            'file_aliases' => [],
            'published_at' => null,
            'extra' => [],
        ];
        $this->assertEquals($expectedSettings, $page->getSettings());
    }

    /**
     * @test
     */
    public function childrenCanBeAssignedAndFetched()
    {
        $parent = new Page('page', ['slug' => 'page']);
        $child1 = new Page('child1', ['slug' => 'child1'], $parent);
        $child2 = new Page('child2', ['slug' => 'child2'], $parent);

        $this->assertSame([
            $child1,
            $child2,
        ], $parent->getChildren());
    }

    /**
     * @test
     */
    public function extraValuesCanBeFetched()
    {
        $page = new Page('page', [
            'slug' => 'page',
            'extra' => [
                'fancy_extra_settings' => 'are not validated',
            ],
        ]);
        $page->validateSettings();

        $this->assertSame('are not validated', $page->getExtraValue('fancy_extra_settings'));
        $this->assertNull($page->getExtraValue('does_not_exist'));
        $this->assertSame('default', $page->getExtraValue('does_not_exist', 'default'));
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
