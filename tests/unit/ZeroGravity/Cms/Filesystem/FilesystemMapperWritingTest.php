<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use Tests\Unit\ZeroGravity\Cms\Test\TempDirTrait;
use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\WritablePage;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

/**
 * @group write
 */
class FilesystemMapperWritingTest extends BaseUnit
{
    use TempDirTrait;

    public function _before()
    {
        $this->setupTempDir($this->getValidPagesDir());
    }

    public function _after()
    {
        $this->cleanupTempDir();
    }

    /**
     * @test
     */
    public function savingThrowsExceptionIfDiffDoesNotContainFilesystemPages()
    {
        $oldPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };
        $newPage = clone $oldPage;
        $diff = new PageDiff($oldPage, $newPage);

        $mapper = $this->getTempValidPagesFilesystemMapper();
        $this->expectException(FilesystemException::class);
        $mapper->saveChanges($diff);
    }

    /**
     * @test
     */
    public function savingThrowsExceptionIfNewNameAlreadyExists()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('02.markdown_only');

        $diff = new PageDiff($oldPage, $newPage);

        $this->expectException(StructureException::class);
        $mapper->saveChanges($diff);
    }

    /**
     * @test
     */
    public function contentIsSavedToExistingMarkdownFile()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];
        $this->assertCount(3, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];

        $this->assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        $this->assertCount(3, $page->getFiles());
    }

    /**
     * @test
     */
    public function contentIsSavedToExistingMarkdownFileWithFrontmatterYaml()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];
        $this->assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw($oldPage->getContentRaw()."\n\nnew **raw** content");

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];

        $this->assertSame("<p>This is the content of page 02.</p>\n<p>new <strong>raw</strong> content</p>", $page->getContent());
        $this->assertSame('value-kept-after-content-update', $page->getExtra('keep-value'));
        $this->assertCount(1, $page->getFiles());
    }

    /**
     * @test
     */
    public function contentIsSavedToDirectoryContainingOnlyYamlFile()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];
        $this->assertCount(4, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        $this->assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        $this->assertCount(5, $page->getFiles());
    }

    /**
     * @test
     */
    public function contentIsSavedToDirectoryContainingOnlyTwigFile()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];
        $this->assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];

        $this->assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        $this->assertCount(2, $page->getFiles());
    }

    /**
     * @test
     */
    public function settingsAreSavedToExistingYamlFile()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];
        $this->assertCount(3, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];

        $this->assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        $this->assertSame('new-value', $page->getExtra('new-key'));
        $this->assertCount(3, $page->getFiles());
    }

    /**
     * @test
     */
    public function settingsAreSavedToExistingMarkdownFileWithFrontmatterYaml()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];
        $this->assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];

        $this->assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        $this->assertSame('new-value', $page->getExtra('new-key'));
        $this->assertCount(1, $page->getFiles());
    }

    /**
     * @test
     */
    public function settingsAreSavedToDirectoryContainingOnlyTwigFile()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];
        $this->assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];

        $this->assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        $this->assertSame('new-value', $page->getExtra('new-key'));
        $this->assertCount(2, $page->getFiles());
    }

    /**
     * @test
     */
    public function pageCanBeRenamed()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/with_children'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('04.still_with_children');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();

        $this->assertArrayNotHasKey('/with_children', $pages);
        $this->assertArrayHasKey('/still_with_children', $pages);

        $page = $pages['/still_with_children'];
        $this->assertCount(2, $page->getChildren());
    }

    /**
     * @test
     */
    public function pageCanBeMovedToAnotherParent()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/with_children'];
        $newParent = $pages['/twig_only'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('moved_but_still_with_children');
        $newPage->setParent($newParent);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();

        $this->assertArrayNotHasKey('/with_children', $pages);

        $parent = $pages['/twig_only'];
        $this->assertArrayHasKey('/twig_only/moved_but_still_with_children', $parent->getChildren()->toArray());

        $page = $parent->getChildren()->toArray()['/twig_only/moved_but_still_with_children'];
        $this->assertCount(2, $page->getChildren());
    }

    /**
     * @test
     * @group new
     */
    public function newPageCanBeSavedInRootDir()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();

        $oldPage = $mapper->getNewWritablePage();
        $newPage = clone $oldPage;
        $newPage->setName('08.totally_new');
        $newPage->setSettings([
            'title' => 'A totally new page!',
            'slug' => 'totally_new',
        ]);
        $newPage->setContentRaw('totally **new** content!');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $this->assertArrayHasKey('/totally_new', $pages);
        $page = $pages['/totally_new'];
        $this->assertSame('A totally new page!', $page->getTitle());
        $this->assertSame('<p>totally <strong>new</strong> content!</p>', $page->getContent());
    }

    /**
     * @test
     * @group new
     */
    public function newPageCanBeSavedInAnotherPageDir()
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $parent = $pages['/yaml_only'];

        $oldPage = $mapper->getNewWritablePage($parent);
        $newPage = clone $oldPage;
        $newPage->setName('08.totally_new');
        $newPage->setSettings([
            'title' => 'A totally new page!',
            'slug' => 'totally_new',
        ]);
        $newPage->setContentRaw('totally **new** content!');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];
        $this->assertCount(1, $page->getChildren());
        $this->assertArrayHasKey('/yaml_only/totally_new', $page->getChildren()->toArray());
    }

    /**
     * @return FilesystemMapper
     */
    protected function getTempValidPagesFilesystemMapper()
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->tempDir);

        return new FilesystemMapper($fileFactory, $this->tempDir, true, [], new NullLogger(), new EventDispatcher());
    }
}
