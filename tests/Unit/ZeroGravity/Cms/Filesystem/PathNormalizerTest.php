<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Codeception\Attribute\DataProvider;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\PathNormalizer;

class PathNormalizerTest extends BaseUnit
{
    #[DataProvider('provideValidPaths')]
    #[Test]
    public function validPathIsNormalized(string $path, string $resolvedPath): void
    {
        $path = new Path($path);
        PathNormalizer::normalizePath($path);

        self::assertSame($resolvedPath, $path->toString());
    }

    public static function provideValidPaths(): Iterator
    {
        yield ['foo/bar/file.ext', 'foo/bar/file.ext'];
        yield ['foo/bar///file.ext', 'foo/bar/file.ext'];
        yield ['/foo/bar/../file.ext', '/foo/file.ext'];
        yield ['foo/bar/../dir/../../file.ext', 'file.ext'];
        yield ['/foo/bar/./dir/../../file.ext', '/foo/file.ext'];
        yield ['foo/bar//dir/../../file.ext', 'foo/file.ext'];
        yield ['', ''];
    }

    #[DataProvider('provideInvalidPaths')]
    #[Test]
    public function invalidPathThrowsException(string $path): void
    {
        $path = new Path($path);

        $this->expectException(UnsafePathException::class);
        PathNormalizer::normalizePath($path);
    }

    public static function provideInvalidPaths(): Iterator
    {
        yield ['../file.ext'];
        yield ['foo/bar/../../../file.ext'];
        yield ['foo/bar/../../../foo/bar/file.ext'];
    }

    #[DataProvider('providePathsWithInPath')]
    #[Test]
    public function validPathAndParentPathIsNormalized(string $path, string $parentPath, string $resolvedPath, string $resolvedParentPath): void
    {
        $path = new Path($path);
        $parentPath = new Path($parentPath);
        PathNormalizer::normalizePath($path, $parentPath);

        self::assertSame($resolvedPath, $path->toString());
        self::assertSame($resolvedParentPath, $parentPath->toString());
    }

    public static function providePathsWithInPath(): Iterator
    {
        yield [
            'foo/bar/file.ext',
            'baz',
            'foo/bar/file.ext',
            'baz',
        ];
        yield [
            '../bar/file.ext',
            'baz/dir',
            'bar/file.ext',
            'baz',
        ];
        yield [
            '../bar/foo/../file.ext',
            'baz/dir',
            'bar/file.ext',
            'baz',
        ];
        yield [
            '../bar/../../foo/../file.ext',
            'baz/dir',
            'file.ext',
            '',
        ];
    }
}
