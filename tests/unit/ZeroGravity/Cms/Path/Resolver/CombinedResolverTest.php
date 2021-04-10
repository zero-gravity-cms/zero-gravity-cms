<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\CombinedResolver;
use ZeroGravity\Cms\Path\Resolver\FilesystemResolver;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

/**
 * @group resolver
 */
class CombinedResolverTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideSingleFilePaths
     *
     * @param string $inPath
     */
    public function singleFilesAreResolvedByPath(string $path, $inPath, string $expectedPath)
    {
        $pageResolver = $this->getPageResolver();
        $filesystemResolver = new FilesystemResolver($this->getDefaultFileFactory());
        $resolver = new CombinedResolver($filesystemResolver, $pageResolver);

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
                'with_children/_child1/child_file3.png',
                null,
                '/04.with_children/_child1/child_file3.png',
            ],
            [
                '/01.yaml_only/file3.png',
                null,
                '/01.yaml_only/file3.png',
            ],
            [
                'sub/dir/child_file7.png',
                '04.with_children/03.empty',
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
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
