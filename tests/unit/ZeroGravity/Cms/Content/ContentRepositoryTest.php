<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Codeception\Util\Stub;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Cache\Simple\ArrayCache;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\StructureParser;

class ContentRepositoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function pagesAreLoadedFromParser()
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $parser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $repo = new ContentRepository($parser, new ArrayCache(), false);

        $this->assertSame([
            $page1,
            $page2,
        ], $repo->getPageTree());

        $this->assertSame([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo->getAllPages());

        $this->assertSame($page3, $repo->getPage('/page2/page3'));
        $this->assertNull($repo->getPage('/does/not/exist'));
    }

    /**
     * @test
     */
    public function pagesAreCachedBetweenInstances()
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $parser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $cache = new ArrayCache();
        $repo = new ContentRepository($parser, $cache, false);
        $repo->getAllPages();

        $emptyParser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [],
        ]);
        $repo2 = new ContentRepository($emptyParser, $cache, false);

        $this->assertEquals([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo2->getAllPages(), 'The parser is never called if pages reside in the cache.');

        $repo3 = new ContentRepository($emptyParser, $cache, false);
        $repo3->clearCache();
        $this->assertEquals([], $repo3->getAllPages(), 'After clearing the cache the empty result is loaded.');
    }

    /**
     * @test
     */
    public function pagesAreNotCachedIfSkipCacheIsSet()
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $parser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $cache = new ArrayCache();
        $repo = new ContentRepository($parser, $cache, false);
        $repo->getAllPages();

        $emptyParser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [],
        ]);
        $repo2 = new ContentRepository($emptyParser, $cache, true);

        $this->assertEquals([], $repo2->getAllPages());
    }

    /**
     * @test
     */
    public function pagesAreLoadedIfCacheThrowsException()
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $parser = Stub::makeEmpty(StructureParser::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);

        $cache = $this->getMockBuilder(ArrayCache::class)
            ->setMethods(['has'])
            ->getMock()
        ;
        $cache->expects($this->once())
            ->method('has')
            ->willThrowException(new InvalidArgumentException())
        ;

        $repo = new ContentRepository($parser, $cache, false);
        $repo->getAllPages();
    }

    /**
     * @param           $name
     * @param Page|null $parent
     *
     * @return Page
     */
    private function createSimplePage($name, Page $parent = null)
    {
        return new Page($name, ['slug' => $name], $parent);
    }
}
