<?php

namespace Tests\Unit\ZeroGravity\Cms\Path;

use Codeception\Attribute\DataProvider;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\Path;

/**
 * Class PathTest.
 */
class PathTest extends BaseUnit
{
    /**
     * @param array<string, int|bool> $expectations
     */
    #[DataProvider('providePathData')]
    #[Test]
    public function pathIsParsedAndAnalyzed(string $pathString, array $expectations): void
    {
        $path = new Path($pathString);

        self::assertCount($expectations['elements'], $path->getElements(), 'element count matches: '.$pathString);
        self::assertSame($expectations['absolute'], $path->isAbsolute(), 'absolute detection matches: '.$pathString);
        self::assertSame($expectations['directory'], $path->isDirectory(), 'directory detection matches: '.$pathString);
        self::assertSame($expectations['regex'], $path->isRegex(), 'regex detection matches: '.$pathString);
        self::assertSame($expectations['glob'], $path->isGlob(), 'glob detection matches: '.$pathString);

        self::assertSame($expectations['elements'] > 0, $path->hasElements());
    }

    /**
     * @param array<string, int|bool> $expectations
     */
    #[DataProvider('providePathData')]
    #[Test]
    public function toStringRebuildsPath(mixed $pathString, array $expectations): void
    {
        if (isset($expectations['cannotRebuild']) && $expectations['cannotRebuild']) {
            // this is for paths containing "empty" elements that get lost during parsing
            return;
        }

        $path = new Path($pathString);
        self::assertSame($pathString, $path->toString(true));
    }

    public static function providePathData(): Iterator
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

    #[Test]
    public function pathCanBeNormalized(): void
    {
        $path = new Path('path/../with/parent');
        $path->normalize();
        self::assertSame('with/parent', $path->toString());
        self::assertSame('with/parent', (string) $path);

        $path = new Path('path/../../leaving/structure');
        $parent = new Path('parent/path');
        $path->normalize($parent);
        self::assertSame('leaving/structure', $path->toString());
        self::assertSame('parent', $parent->toString());
    }

    #[Test]
    public function regexPathIsNotNormalized(): void
    {
        $path = new Path('#valid/regex/../stuff#');
        $path->normalize();
        self::assertSame('#valid/regex/../stuff#', $path->toString());
    }

    #[Test]
    public function normalizedPathDoesNotEndUpAsDoubleSlash(): void
    {
        $path = new Path('/foo/../');
        $path->normalize();
        self::assertSame('/', $path->toString());
    }

    /**
     * @param array<string, int|bool> $expect
     */
    #[DataProvider('provideAppendedPathData')]
    #[Test]
    public function appendReturnsAppendedPathWithChildSettings(
        string $pathString,
        string $childString,
        string $newString,
        array $expect
    ): void {
        $path = new Path($pathString);
        $child = new Path($childString);

        $newPath = $path->appendPath($child);

        self::assertSame($newString, $newPath->toString());
        self::assertNotSame($path, $newPath, 'appendPath returns new path instance');

        $newPathStr = $newPath->toString();
        self::assertCount($expect['elements'], $newPath->getElements(), 'element count matches: '.$newPathStr);
        self::assertSame($expect['absolute'], $newPath->isAbsolute(), 'absolute detection matches: '.$newPathStr);
        self::assertSame($expect['directory'], $newPath->isDirectory(), 'directory detection matches: '.$newPathStr);
        self::assertSame($expect['regex'], $newPath->isRegex(), 'regex detection matches: '.$newPathStr);
        self::assertSame($expect['glob'], $newPath->isGlob(), 'glob detection matches: '.$newPathStr);
    }

    public static function provideAppendedPathData(): Iterator
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

    #[DataProvider('provideDirectoryPathData')]
    #[Test]
    public function getDirectoryReturnsNewPathInstanceContainingPathDirectory(string $pathString, mixed $expectedDirectoryPath): void
    {
        $path = new Path($pathString);
        $directory = $path->getDirectory();

        self::assertSame($expectedDirectoryPath, $directory->toString());
        self::assertNotSame($path, $directory);
    }

    public static function provideDirectoryPathData(): Iterator
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

    #[DataProvider('provideFilePathData')]
    #[Test]
    public function getFileReturnsNewPathInstanceContainingPathFile(string $pathString, ?string $expectedFilePath): void
    {
        $path = new Path($pathString);
        $file = $path->getFile();

        if (null === $expectedFilePath) {
            self::assertNull($file);
        } else {
            self::assertSame($expectedFilePath, $file->toString());
        }
        self::assertNotSame($path, $file);
    }

    public static function provideFilePathData(): Iterator
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

    #[Test]
    public function getLastElementReturnsLastElement(): void
    {
        $path = new Path('sample/path/string');
        self::assertSame('string', $path->getLastElement()->getName());
    }

    #[Test]
    public function getLastElementReturnsNullForEmptyPath(): void
    {
        $path = new Path('/');
        self::assertNull($path->getLastElement());
    }

    #[Test]
    public function dropLastElementModifiesPathInstanceAndMakesItDirectory(): void
    {
        $path = new Path('sample/path/string');
        $path->dropLastElement();
        self::assertSame('sample/path/', $path->toString());
        self::assertTrue($path->isDirectory());
    }
}
