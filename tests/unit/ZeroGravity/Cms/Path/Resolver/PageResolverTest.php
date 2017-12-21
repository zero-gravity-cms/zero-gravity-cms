<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

class PageResolverTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideSingleFilePaths
     *
     * @param string $path
     * @param string $inPath
     * @param string $expectedPath
     */
    public function singleFilesAreResolvedByPath(string $path, $inPath, string $expectedPath)
    {
        $resolver = $this->getPageResolver();
        $file = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        $this->assertInstanceOf(File::class, $file, "Page $path was found in $inPath");
        $this->assertSame($expectedPath, $file->getPathname());
    }

    public function provideSingleFilePaths()
    {
        return [
            [
                '/yaml_only/file2.png',
                null,
                '/01.yaml_only/file2.png',
            ],
            [
                'yaml_only/file2.png',
                null,
                '/01.yaml_only/file2.png',
            ],
            [
                'with_children/_child1/child_file3.png',
                null,
                '/04.with_children/_child1/child_file3.png',
            ],
            [
                '_child1/child_file3.png',
                'with_children/',
                '/04.with_children/_child1/child_file3.png',
            ],
            [
                'with_children/03.empty/sub/dir/child_file7.png',
                null,
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNonExistingPagePaths
     *
     * @param string $path
     * @param string $inPath
     */
    public function singlePagesThatAreNotFound(string $path, $inPath)
    {
        $resolver = $this->getPageResolver();
        $pageFile = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        $this->assertNull($pageFile);
    }

    public function provideNonExistingPagePaths()
    {
        return [
            [
                '01.yaml_only',
                null,
            ],
            [
                'yaml_only/file4.png',
                null,
            ],
            [
                '',
                null,
            ],
        ];
    }

    /**
     * @return PageResolver
     */
    private function getPageResolver()
    {
        $resolver = new PageResolver($this->getDefaultContentRepository());

        return $resolver;
    }
}
