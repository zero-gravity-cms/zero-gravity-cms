<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Codeception\Attribute\DataProvider;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

class PageResolverTest extends BaseUnit
{
    #[DataProvider('provideSingleFilePaths')]
    #[Test]
    public function singleFilesAreResolvedByPath(string $path, ?string $inPath, string $expectedPath): void
    {
        $resolver = $this->getPageResolver();
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

    #[DataProvider('provideNonExistingPagePaths')]
    #[Test]
    public function singlePagesThatAreNotFound(string $path, ?string $inPath): void
    {
        $resolver = $this->getPageResolver();
        $pageFile = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        self::assertNull($pageFile);
    }

    public static function provideNonExistingPagePaths(): Iterator
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
        yield [
            '_child1/this-does-not-exist.png',
            'with_children/',
        ];
    }

    private function getPageResolver(): PageResolver
    {
        return new PageResolver($this->getDefaultContentRepository());
    }
}
