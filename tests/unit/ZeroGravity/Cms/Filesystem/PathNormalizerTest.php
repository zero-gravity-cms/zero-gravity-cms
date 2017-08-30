<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\PathNormalizer;

class PathNormalizerTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideValidPaths
     *
     * @param $path
     * @param $resolvedPath
     */
    public function validPath(string $path, string $resolvedPath)
    {
        $this->assertSame($resolvedPath, PathNormalizer::normalize($path));
    }

    public function provideValidPaths()
    {
        return [
            ['foo/bar/file.ext', 'foo/bar/file.ext'],
            ['foo/bar///file.ext', 'foo/bar/file.ext'],
            ['/foo/bar/../file.ext', 'foo/file.ext'],
            ['foo/bar/../dir/../../file.ext', 'file.ext'],
            ['/foo/bar/./dir/../../file.ext', 'foo/file.ext'],
            ['foo/bar//dir/../../file.ext', 'foo/file.ext'],
            ['', ''],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidPaths
     *
     * @param $path
     */
    public function invalidPath(string $path)
    {
        $this->expectException(UnsafePathException::class);
        PathNormalizer::normalize($path);
    }

    public function provideInvalidPaths()
    {
        return [
            ['../file.ext'],
            ['foo/bar/../../../file.ext'],
            ['foo/bar/../../../foo/bar/file.ext'],
        ];
    }

    /**
     * @test
     * @dataProvider providePathsWithInPath
     *
     * @param string $path
     * @param string $inPath
     * @param string $resolvedPath
     * @param string $resolvedInPath
     */
    public function validPathAndInPath(string $path, string $inPath, string $resolvedPath, string $resolvedInPath)
    {
        $this->assertSame($resolvedPath, PathNormalizer::normalize($path, $inPath));
        $this->assertSame($resolvedInPath, $inPath);
    }

    public function providePathsWithInPath()
    {
        return [
            [
                'foo/bar/file.ext',
                'baz',
                'foo/bar/file.ext',
                'baz',
            ],
            [
                '../bar/file.ext',
                'baz/dir',
                'bar/file.ext',
                'baz',
            ],
            [
                '../bar/foo/../file.ext',
                'baz/dir',
                'bar/file.ext',
                'baz',
            ],
            [
                '../bar/../../foo/../file.ext',
                'baz/dir',
                'file.ext',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideRelativePaths
     *
     * @param string $from
     * @param string $to
     * @param string $expectedFrom
     * @param string $expectedTo
     */
    public function testMoveRelativeParts(string $from, string $to, string $expectedFrom, string $expectedTo)
    {
    }

    public function provideRelativePaths()
    {
        return [
            [
                'foo/bar.txt',
                '',
                'bar.txt',
                'foo',
            ],
            [
                'foo/laa/bar.txt',
                '',
                'bar.txt',
                'foo/laa',
            ],
            [
                'foo/laa/bar.txt',
                'baz',
                'bar.txt',
                'baz/foo/laa',
            ],
            [
                'images/person_?.png',
                '',
                'person_?.png',
                'images',
            ],
        ];
    }
}
