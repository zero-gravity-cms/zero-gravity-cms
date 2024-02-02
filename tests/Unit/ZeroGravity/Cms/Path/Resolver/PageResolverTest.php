<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Iterator;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

class PageResolverTest extends BaseUnit
{
    /**
     * @test
     *
     * @dataProvider provideSingleFilePaths
     *
     * @param string $inPath
     */
    public function singleFilesAreResolvedByPath(string $path, $inPath, string $expectedPath)
    {
        $resolver = $this->getPageResolver();
        $file = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        static::assertInstanceOf(File::class, $file, "Page $path was found in $inPath");
        static::assertSame($expectedPath, $file->getPathname());
    }

    public function provideSingleFilePaths(): Iterator
    {
        yield [
            '/yaml_only/file2.png',
            null,
            '/01.yaml_only/file2.png',
        ];
        yield [
            'yaml_only/file2.png',
            null,
            '/01.yaml_only/file2.png',
        ];
        yield [
            'with_children/_child1/child_file3.png',
            null,
            '/04.with_children/_child1/child_file3.png',
        ];
        yield [
            '_child1/child_file3.png',
            'with_children/',
            '/04.with_children/_child1/child_file3.png',
        ];
        yield [
            'with_children/03.empty/sub/dir/child_file7.png',
            null,
            '/04.with_children/03.empty/sub/dir/child_file7.png',
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideNonExistingPagePaths
     *
     * @param string $inPath
     */
    public function singlePagesThatAreNotFound(string $path, $inPath)
    {
        $resolver = $this->getPageResolver();
        $pageFile = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        static::assertNull($pageFile);
    }

    public function provideNonExistingPagePaths(): Iterator
    {
        yield [
            '01.yaml_only',
            null,
        ];
        yield [
            'yaml_only/file4.png',
            null,
        ];
        yield [
            '',
            null,
        ];
    }

    /**
     * @return PageResolver
     */
    private function getPageResolver()
    {
        return new PageResolver($this->getDefaultContentRepository());
    }
}
