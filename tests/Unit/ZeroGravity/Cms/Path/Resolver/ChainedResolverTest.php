<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\ChainedResolver;
use ZeroGravity\Cms\Path\Resolver\FilesystemResolver;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

#[Group('resolver')]
class ChainedResolverTest extends BaseUnit
{
    #[DataProvider('provideSingleFilePaths')]
    #[Test]
    public function singleFilesAreResolvedByPath(string $path, ?string $inPath, string $expectedPath): void
    {
        $pageResolver = $this->getPageResolver();
        $filesystemResolver = new FilesystemResolver($this->getDefaultFileFactory());
        $resolver = new ChainedResolver([$filesystemResolver, $pageResolver]);

        $file = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        self::assertInstanceOf(File::class, $file, "Page {$path} was found in {$inPath}");
        self::assertSame($expectedPath, $file->getPathname());
    }

    public static function provideSingleFilePaths(): Iterator
    {
        yield [
            '/yaml_only/file2.png',
            null,
            '/01.yaml_only/file2.png',
        ];
        yield [
            'with_children/_child1/child_file3.png',
            null,
            '/04.with_children/_child1/child_file3.png',
        ];
        yield [
            '/01.yaml_only/file3.png',
            null,
            '/01.yaml_only/file3.png',
        ];
        yield [
            'sub/dir/child_file7.png',
            '04.with_children/03.empty',
            '/04.with_children/03.empty/sub/dir/child_file7.png',
        ];
    }

    private function getPageResolver(): PageResolver
    {
        return new PageResolver($this->getDefaultContentRepository());
    }
}
