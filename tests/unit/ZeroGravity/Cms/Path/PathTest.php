<?php

namespace Tests\Unit\ZeroGravity\Cms\Path;

use Iterator;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\Path;

/**
 * Class PathTest.
 */
class PathTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider providePathData
     *
     * @param $pathString
     */
    public function pathIsParsedAndAnalyzed($pathString, array $expectations)
    {
        $path = new Path($pathString);

        static::assertCount($expectations['elements'], $path->getElements(), 'element count matches: '.$pathString);
        static::assertSame($expectations['absolute'], $path->isAbsolute(), 'absolute detection matches: '.$pathString);
        static::assertSame($expectations['directory'], $path->isDirectory(), 'directory detection matches: '.$pathString);
        static::assertSame($expectations['regex'], $path->isRegex(), 'regex detection matches: '.$pathString);
        static::assertSame($expectations['glob'], $path->isGlob(), 'glob detection matches: '.$pathString);

        static::assertSame($expectations['elements'] > 0, $path->hasElements());
    }

    /**
     * @test
     * @dataProvider providePathData
     *
     * @param $pathString
     */
    public function toStringRebuildsPath($pathString, array $expectations)
    {
        if (isset($expectations['cannotRebuild']) && $expectations['cannotRebuild']) {
            // this is for paths containing "empty" elements that get lost during parsing
            return;
        }

        $path = new Path($pathString);
        static::assertSame($pathString, $path->toString(true));
    }

    public function providePathData(): Iterator
    {
        yield [
            '/foo/bar.txt',
            [
                'elements' => 2,
                'absolute' => true,
                'directory' => false,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            'bar.txt',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => false,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            'foo/',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            'foo*/',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => true,
            ],
        ];
        yield [
            '/foo*/',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => false,
                'regex' => true,
                'glob' => false,
            ],
        ];
        yield [
            '/foo**/',
            [
                'elements' => 1,
                'absolute' => true,
                'directory' => true,
                'regex' => false,
                'glob' => true,
            ],
        ];
        yield [
            '/valid\/regex.*\/contains_slashes/',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => false,
                'regex' => true,
                'glob' => false,
            ],
        ];
        yield [
            '#valid/regex.*/contains_slashes#',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => false,
                'regex' => true,
                'glob' => false,
            ],
        ];
        yield [
            '#invalid/regex.*/path',
            [
                'elements' => 3,
                'absolute' => false,
                'directory' => false,
                'regex' => false,
                'glob' => true,
            ],
        ];
        yield [
            'path//containing/./empty/elements/',
            [
                'elements' => 4,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
                'cannotRebuild' => true,
            ],
        ];
        yield [
            'path/../with/parent/../elements/',
            [
                'elements' => 6,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            'path/with/#inline#/regex',
            [
                'elements' => 4,
                'absolute' => false,
                'directory' => false,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            '././',
            [
                'elements' => 0,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
                'cannotRebuild' => true,
            ],
        ];
    }

    /**
     * @test
     */
    public function pathCanBeNormalized()
    {
        $path = new Path('path/../with/parent');
        $path->normalize();
        static::assertSame('with/parent', $path->toString());
        static::assertSame('with/parent', (string) $path);

        $path = new Path('path/../../leaving/structure');
        $parent = new Path('parent/path');
        $path->normalize($parent);
        static::assertSame('leaving/structure', $path->toString());
        static::assertSame('parent', $parent->toString());
    }

    /**
     * @test
     */
    public function regexPathIsNotNormalized()
    {
        $path = new Path('#valid/regex/../stuff#');
        $path->normalize();
        static::assertSame('#valid/regex/../stuff#', $path->toString());
    }

    /**
     * @test
     */
    public function normalizedPathDoesNotEndUpAsDoubleSlash()
    {
        $path = new Path('/foo/../');
        $path->normalize();
        static::assertSame('/', $path->toString());
    }

    /**
     * @test
     * @dataProvider provideAppendedPathData
     */
    public function appendReturnsAppendedPathWithChildSettings(
        string $pathString,
        string $childString,
        string $newString,
        array $expect
    ) {
        $path = new Path($pathString);
        $child = new Path($childString);

        $newPath = $path->appendPath($child);

        static::assertSame($newString, $newPath->toString());
        static::assertNotSame($path, $newPath, 'appendPath returns new path instance');

        $newPathStr = $newPath->toString();
        static::assertCount($expect['elements'], $newPath->getElements(), 'element count matches: '.$newPathStr);
        static::assertSame($expect['absolute'], $newPath->isAbsolute(), 'absolute detection matches: '.$newPathStr);
        static::assertSame($expect['directory'], $newPath->isDirectory(), 'directory detection matches: '.$newPathStr);
        static::assertSame($expect['regex'], $newPath->isRegex(), 'regex detection matches: '.$newPathStr);
        static::assertSame($expect['glob'], $newPath->isGlob(), 'glob detection matches: '.$newPathStr);
    }

    public function provideAppendedPathData(): Iterator
    {
        yield [
            '/foo/bar',
            'baz.txt',
            '/foo/bar/baz.txt',
            [
                'elements' => 3,
                'absolute' => true,
                'directory' => false,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            'bar.txt',
            'baz/',
            'bar.txt/baz/',
            [
                'elements' => 2,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            '',
            'baz/',
            'baz/',
            [
                'elements' => 1,
                'absolute' => false,
                'directory' => true,
                'regex' => false,
                'glob' => false,
            ],
        ];
        yield [
            '/',
            'baz/',
            '/baz/',
            [
                'elements' => 1,
                'absolute' => true,
                'directory' => true,
                'regex' => false,
                'glob' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideDirectoryPathData
     *
     * @param $pathString
     * @param $expectedDirectoryPath
     */
    public function getDirectoryReturnsNewPathInstanceContainingPathDirectory($pathString, $expectedDirectoryPath)
    {
        $path = new Path($pathString);
        $directory = $path->getDirectory();

        static::assertSame($expectedDirectoryPath, $directory->toString());
        static::assertNotSame($path, $directory);
    }

    public function provideDirectoryPathData(): Iterator
    {
        yield [
            '/foo/bar',
            '/foo/',
        ];
        yield [
            '/foo/bar/',
            '/foo/bar/',
        ];
        yield [
            '/foo',
            '/',
        ];
        yield [
            'foo',
            '/',
        ];
        yield [
            '',
            '/',
        ];
    }

    /**
     * @test
     * @dataProvider provideFilePathData
     *
     * @param $pathString
     * @param $expectedFilePath
     */
    public function getFileReturnsNewPathInstanceContainingPathFile($pathString, $expectedFilePath)
    {
        $path = new Path($pathString);
        $file = $path->getFile();

        if (null === $expectedFilePath) {
            static::assertNull($file);
        } else {
            static::assertSame($expectedFilePath, $file->toString());
        }
        static::assertNotSame($path, $file);
    }

    public function provideFilePathData(): Iterator
    {
        yield [
            '/foo/baz/bar',
            'bar',
        ];
        yield [
            '/foo/bar/',
            null,
        ];
        yield [
            '/foo',
            'foo',
        ];
        yield [
            'foo',
            'foo',
        ];
        yield [
            '',
            null,
        ];
        yield [
            'foo/bar.jpg',
            'bar.jpg',
        ];
    }

    /**
     * @test
     */
    public function getLastElementReturnsLastElement()
    {
        $path = new Path('sample/path/string');
        static::assertSame('string', $path->getLastElement()->getName());
    }

    /**
     * @test
     */
    public function getLastElementReturnsNullForEmptyPath()
    {
        $path = new Path('/');
        static::assertNull($path->getLastElement());
    }

    /**
     * @test
     */
    public function dropLastElementModifiesPathInstanceAndMakesItDirectory()
    {
        $path = new Path('sample/path/string');
        $path->dropLastElement();
        static::assertSame('sample/path/', $path->toString());
        static::assertTrue($path->isDirectory());
    }
}
