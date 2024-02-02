<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Iterator;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\PathNormalizer;

class PathNormalizerTest extends BaseUnit
{
    /**
     * @test
     *
     * @dataProvider provideValidPaths
     */
    public function validPathIsNormalized(string $path, string $resolvedPath)
    {
        $path = new Path($path);
        PathNormalizer::normalizePath($path);

        static::assertSame($resolvedPath, $path->toString());
    }

    public function provideValidPaths(): Iterator
    {
        yield ['foo/bar/file.ext', 'foo/bar/file.ext'];
        yield ['foo/bar///file.ext', 'foo/bar/file.ext'];
        yield ['/foo/bar/../file.ext', '/foo/file.ext'];
        yield ['foo/bar/../dir/../../file.ext', 'file.ext'];
        yield ['/foo/bar/./dir/../../file.ext', '/foo/file.ext'];
        yield ['foo/bar//dir/../../file.ext', 'foo/file.ext'];
        yield ['', ''];
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidPaths
     */
    public function invalidPathThrowsException(string $path)
    {
        $path = new Path($path);

        $this->expectException(UnsafePathException::class);
        PathNormalizer::normalizePath($path);
    }

    public function provideInvalidPaths(): Iterator
    {
        yield ['../file.ext'];
        yield ['foo/bar/../../../file.ext'];
        yield ['foo/bar/../../../foo/bar/file.ext'];
    }

    /**
     * @test
     *
     * @dataProvider providePathsWithInPath
     */
    public function validPathAndParentPathIsNormalized(string $path, string $parentPath, string $resolvedPath, string $resolvedParentPath)
    {
        $path = new Path($path);
        $parentPath = new Path($parentPath);
        PathNormalizer::normalizePath($path, $parentPath);

        static::assertSame($resolvedPath, $path->toString());
        static::assertSame($resolvedParentPath, $parentPath->toString());
    }

    public function providePathsWithInPath(): Iterator
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
