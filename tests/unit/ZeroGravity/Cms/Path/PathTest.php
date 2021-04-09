<?php

namespace Tests\Unit\ZeroGravity\Cms\Path;

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

        $this->assertCount($expectations['elements'], $path->getElements(), 'element count matches: '.$pathString);
        $this->assertSame($expectations['absolute'], $path->isAbsolute(), 'absolute detection matches: '.$pathString);
        $this->assertSame($expectations['directory'], $path->isDirectory(),
            'directory detection matches: '.$pathString
        );
        $this->assertSame($expectations['regex'], $path->isRegex(), 'regex detection matches: '.$pathString);
        $this->assertSame($expectations['glob'], $path->isGlob(), 'glob detection matches: '.$pathString);

        $this->assertSame($expectations['elements'] > 0, $path->hasElements());
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
        $this->assertSame($pathString, $path->toString(true));
    }

    public function providePathData()
    {
        return [
            [
                '/foo/bar.txt',
                [
                    'elements' => 2,
                    'absolute' => true,
                    'directory' => false,
                    'regex' => false,
                    'glob' => false,
                ],
            ],
            [
                'bar.txt',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => false,
                    'glob' => false,
                ],
            ],
            [
                'foo/',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => true,
                    'regex' => false,
                    'glob' => false,
                ],
            ],
            [
                'foo*/',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => true,
                    'regex' => false,
                    'glob' => true,
                ],
            ],
            [
                '/foo*/',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => true,
                    'glob' => false,
                ],
            ],
            [
                '/foo**/',
                [
                    'elements' => 1,
                    'absolute' => true,
                    'directory' => true,
                    'regex' => false,
                    'glob' => true,
                ],
            ],
            [
                '/valid\/regex.*\/contains_slashes/',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => true,
                    'glob' => false,
                ],
            ],
            [
                '#valid/regex.*/contains_slashes#',
                [
                    'elements' => 1,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => true,
                    'glob' => false,
                ],
            ],
            [
                '#invalid/regex.*/path',
                [
                    'elements' => 3,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => false,
                    'glob' => true,
                ],
            ],
            [
                'path//containing/./empty/elements/',
                [
                    'elements' => 4,
                    'absolute' => false,
                    'directory' => true,
                    'regex' => false,
                    'glob' => false,
                    'cannotRebuild' => true,
                ],
            ],
            [
                'path/../with/parent/../elements/',
                [
                    'elements' => 6,
                    'absolute' => false,
                    'directory' => true,
                    'regex' => false,
                    'glob' => false,
                ],
            ],
            [
                'path/with/#inline#/regex',
                [
                    'elements' => 4,
                    'absolute' => false,
                    'directory' => false,
                    'regex' => false,
                    'glob' => false,
                ],
            ],
            [
                '././',
                [
                    'elements' => 0,
                    'absolute' => false,
                    'directory' => true,
                    'regex' => false,
                    'glob' => false,
                    'cannotRebuild' => true,
                ],
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
        $this->assertSame('with/parent', $path->toString());
        $this->assertSame('with/parent', (string) $path);

        $path = new Path('path/../../leaving/structure');
        $parent = new Path('parent/path');
        $path->normalize($parent);
        $this->assertSame('leaving/structure', $path->toString());
        $this->assertSame('parent', $parent->toString());
    }

    /**
     * @test
     */
    public function regexPathIsNotNormalized()
    {
        $path = new Path('#valid/regex/../stuff#');
        $path->normalize();
        $this->assertSame('#valid/regex/../stuff#', $path->toString());
    }

    /**
     * @test
     */
    public function normalizedPathDoesNotEndUpAsDoubleSlash()
    {
        $path = new Path('/foo/../');
        $path->normalize();
        $this->assertSame('/', $path->toString());
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

        $this->assertSame($newString, $newPath->toString());
        $this->assertNotSame($path, $newPath, 'appendPath returns new path instance');

        $newPathStr = $newPath->toString();
        $this->assertCount($expect['elements'], $newPath->getElements(), 'element count matches: '.$newPathStr);
        $this->assertSame($expect['absolute'], $newPath->isAbsolute(), 'absolute detection matches: '.$newPathStr);
        $this->assertSame($expect['directory'], $newPath->isDirectory(), 'directory detection matches: '.$newPathStr);
        $this->assertSame($expect['regex'], $newPath->isRegex(), 'regex detection matches: '.$newPathStr);
        $this->assertSame($expect['glob'], $newPath->isGlob(), 'glob detection matches: '.$newPathStr);
    }

    public function provideAppendedPathData()
    {
        return [
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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

        $this->assertSame($expectedDirectoryPath, $directory->toString());
        $this->assertNotSame($path, $directory);
    }

    public function provideDirectoryPathData()
    {
        return [
            [
                '/foo/bar',
                '/foo/',
            ],
            [
                '/foo/bar/',
                '/foo/bar/',
            ],
            [
                '/foo',
                '/',
            ],
            [
                'foo',
                '/',
            ],
            [
                '',
                '/',
            ],
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
            $this->assertNull($file);
        } else {
            $this->assertSame($expectedFilePath, $file->toString());
        }
        $this->assertNotSame($path, $file);
    }

    public function provideFilePathData()
    {
        return [
            [
                '/foo/baz/bar',
                'bar',
            ],
            [
                '/foo/bar/',
                null,
            ],
            [
                '/foo',
                'foo',
            ],
            [
                'foo',
                'foo',
            ],
            [
                '',
                null,
            ],
            [
                'foo/bar.jpg',
                'bar.jpg',
            ],
        ];
    }

    /**
     * @test
     */
    public function getLastElementReturnsLastElement()
    {
        $path = new Path('sample/path/string');
        $this->assertSame('string', $path->getLastElement()->getName());
    }

    /**
     * @test
     */
    public function getLastElementReturnsNullForEmptyPath()
    {
        $path = new Path('/');
        $this->assertNull($path->getLastElement());
    }

    /**
     * @test
     */
    public function dropLastElementModifiesPathInstanceAndMakesItDirectory()
    {
        $path = new Path('sample/path/string');
        $path->dropLastElement();
        $this->assertSame('sample/path/', $path->toString());
        $this->assertTrue($path->isDirectory());
    }
}
