<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder;

use Symfony\Component\Cache\Simple\ArrayCache;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Filesystem\FilesystemParser;

/**
 * @group finder
 */
class PageFinderTest extends BaseUnit
{
    /**
     * @test
     */
    public function basicPageFinderReturnsAllPagesRecursively()
    {
        $finder = $this->getFinder();
        $this->assertCount(11, $finder);
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredBySlug()
    {
        $finder = $this->getFinder()
            ->slug('yaml_and_twig')
        ;
        $this->assertCount(1, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->slug('child?')
        ;
        $this->assertCount(4, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->slug('/.*Chil.*/i')
        ;
        $this->assertCount(5, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByName()
    {
        $finder = $this->getFinder()
            ->name('04.with_children')
        ;
        $this->assertCount(1, $finder, 'String comparison');

        $finder = $this->getFinder()
            ->name('0?.child?')
        ;
        $this->assertCount(4, $finder, 'Glob comparison');

        $finder = $this->getFinder()
            ->name('/.*Chil.*/i')
        ;
        $this->assertCount(5, $finder, 'Regex comparison');
    }

    /**
     * @test
     */
    public function pagesCanBeFilteredByDepth()
    {
        $finder = $this->getFinder()
            ->depth(0)
        ;
        $this->assertCount(7, $finder, 'Depth 0');

        $finder = $this->getFinder()
            ->depth('> 0')
        ;
        $this->assertCount(4, $finder, 'Depth > 0');

        $finder = $this->getFinder()
            ->depth('>= 0')
        ;
        $this->assertCount(11, $finder, 'Depth >= 0');

        $finder = $this->getFinder()
            ->depth(1)
        ;
        $this->assertCount(4, $finder, 'Depth 1');

        $finder = $this->getFinder()
            ->depth(2)
        ;
        $this->assertCount(0, $finder, 'Depth 2');
    }

    /**
     * @return PageFinder
     */
    private function getFinder()
    {
        return $this->getRepository()->getPageFinder();
    }

    /**
     * @return ContentRepository
     */
    private function getRepository()
    {
        $fileFactory = $this->getDefaultFileFactory();
        $path = $this->getValidPagesDir();
        $parser = new FilesystemParser($fileFactory, $path, false, []);

        return new ContentRepository($parser, new ArrayCache(), false);
    }
}
