<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;
use ZeroGravity\Cms\Filesystem\Event\AfterCreatePage;
use ZeroGravity\Cms\Filesystem\Event\BeforeCreatePage;
use ZeroGravity\Cms\Filesystem\PageFactory;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

/**
 * @group pagefactory
 */
class PageFactoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function emptyDirectoryDataReturnsNoPage()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/invalid_pages/no_data');
        $page = $pageFactory->createPage($dir, false, [], null);

        $this->assertNull($page);
    }

    /**
     * @test
     * @dataProvider provideValidDirectories
     *
     * @param string $path
     */
    public function validDirectoryDataReturnsPage($path)
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/'.$path);
        $page = $pageFactory->createPage($dir, false, [], null);

        $this->assertInstanceOf(Page::class, $page);
    }

    public function provideValidDirectories()
    {
        return [
            ['01.yaml_only'],
            ['02.markdown_only'],
            ['03.yaml_and_markdown_and_twig'],
            ['04.with_children'],
            ['05.twig_only'],
            ['06.yaml_and_twig'],
            ['no_sorting_prefix'],
        ];
    }

    /**
     * @test
     */
    public function yamlDataIsParsed()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [], null);

        $this->assertSame('testtitle', $page->getTitle());
    }

    /**
     * @test
     */
    public function defaultSettingsCanBeSet()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [
            'title' => 'defaulttitle',
            'menu_id' => 'defaultmenu',
        ], null);

        $this->assertSame('testtitle', $page->getTitle(), 'YAML settings override default settings');
        $this->assertSame('defaultmenu', $page->getMenuId(), 'default settings override empty settings');
    }

    /**
     * @test
     */
    public function createdPageContainsFiles()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page = $pageFactory->createPage($dir, false, [], null);

        $this->assertEquals([
            'page.yaml',
            '03.empty/child_file5.png',
            '03.empty/child_file6.png',
            '03.empty/this_is_no_page.txt',
            '03.empty/sub/dir/child_file7.png',
            '03.empty/sub/dir/child_file8.png',
        ], array_keys($page->getFiles()));
    }

    /**
     * @test
     */
    public function defaultTemplateIsSetIfSingleTwigFileWithBasenameIsPresent()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/03.yaml_and_markdown_and_twig');
        $parentPage = null;
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        $this->assertEquals('@ZeroGravity/name.html.twig', $page->getContentTemplate());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/06.yaml_and_twig');
        $parentPage = new Page('06.yaml_and_twig');
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        $this->assertEquals('@ZeroGravity/06.yaml_and_twig/page.html.twig', $page->getContentTemplate());
    }

    /**
     * @test
     */
    public function pagesAreEqualIfParsedMultipleTimes()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page1 = $pageFactory->createPage($dir, false, [], null);
        $page2 = $pageFactory->createPage($dir, false, [], null);

        $this->assertEquals($page1, $page2);
    }

    /**
     * @test
     */
    public function parentsChildDefaultsAreAppliedToChildPagesAndMerged()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());
        $parentPage = new Page('page', [
            'child_defaults' => [
                'extra' => [
                    'some key' => 'some value',
                ],
            ],
        ]);

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [], $parentPage);

        $this->assertEquals('some value', $page->getExtra('some key'));
        $this->assertEquals('another_custom_value', $page->getExtra('custom'));
    }

    /**
     * @test
     */
    public function eventsAreDispatchedDuringCreatePage()
    {
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $run = 0;

        $beforeCreatePageCallback = function ($argument) {
            if (!$argument instanceof BeforeCreatePage) {
                return false;
            }
            if ('01.yaml_only' !== $argument->getDirectory()->getName()) {
                return false;
            }
            if ('yaml_only' !== $argument->getSettings()['slug']) {
                return false;
            }
            if (null !== $argument->getParentPage()) {
                return false;
            }

            return true;
        };

        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeCreatePage::BEFORE_CREATE_PAGE, $this->callback($beforeCreatePageCallback))
            ->willReturnArgument(1)
        ;

        $afterCreatePageCallback = function ($argument) {
            if (!$argument instanceof AfterCreatePage) {
                return false;
            }
            if ('testtitle' !== $argument->getPage()->getTitle()) {
                return false;
            }

            return true;
        };

        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterCreatePage::AFTER_CREATE_PAGE, $this->callback($afterCreatePageCallback))
            ->willReturnArgument(1)
        ;

        $pageFactory = new PageFactory(new NullLogger(), $dispatcher);
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $pageFactory->createPage($dir, false, []);
    }

    /**
     * @test
     */
    public function settingsCanBeModifiedDuringBeforeCreatePage()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BeforeCreatePage::BEFORE_CREATE_PAGE, function (BeforeCreatePage $event) {
            $settings = $event->getSettings();
            $settings['extra']['very_custom_key'] = 'very custom value';
            $event->setSettings($settings);
        });

        $pageFactory = new PageFactory(new NullLogger(), $dispatcher);
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, []);

        $this->assertSame('very custom value', $page->getExtra('very_custom_key'));
    }

    /**
     * @param string $path
     *
     * @return Directory
     */
    private function createParsedDirectoryFromPath(string $path)
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $directory = new Directory(new \SplFileInfo($path), $fileFactory);

        return $directory;
    }
}
