<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Exception\ResolverException;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\Path;

class FilesystemResolverTest extends BaseUnit
{
    #[DataProvider('provideSingleValidFiles')]
    #[Group('resolver')]
    #[Test]
    public function singleValidFile(string $file, ?string $inPath, string $pathName): void
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->get(new Path($file), $parent);
        self::assertInstanceOf(File::class, $resolved, 'path results in file: '.$file);
        self::assertSame($pathName, $resolved->getPathname(), 'pathname matches');
    }

    public static function provideSingleValidFiles(): Iterator
    {
        yield [
            '01.yaml_only/file1.png',
            null,
            '/01.yaml_only/file1.png',
        ];
        yield [
            '/01.yaml_only/file3.png',
            null,
            '/01.yaml_only/file3.png',
        ];
        yield [
            'root_file1.png',
            null,
            '/root_file1.png',
        ];
        yield [
            '/root_file2.png',
            null,
            '/root_file2.png',
        ];
        yield [
            '04.with_children/03.empty/sub/dir/child_file7.png',
            null,
            '/04.with_children/03.empty/sub/dir/child_file7.png',
        ];
        yield [
            'file1.png',
            '/01.yaml_only',
            '/01.yaml_only/file1.png',
        ];
        yield [
            'sub/dir/child_file7.png',
            '04.with_children/03.empty',
            '/04.with_children/03.empty/sub/dir/child_file7.png',
        ];
        yield [
            'sub/dir/child_file7.png',
            '/04.with_children/03.empty/',
            '/04.with_children/03.empty/sub/dir/child_file7.png',
        ];
    }

    #[Test]
    public function parentDirectoryNotationIsNormalized(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('foo/bar/../../root_file1.png'));
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/root_file1.png', $resolved->getPathname(), 'pathname matches');
    }

    #[Test]
    public function singleFileCanReachRelativePathOutsideInPath(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('../../images/person_a.png'), new Path('04.with_children/_child1'));
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/images/person_a.png', $resolved->getPathname(), 'pathname matches');
    }

    #[DataProvider('provideSingleInvalidFiles')]
    #[Test]
    public function singleInvalidFile(string $file): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path($file));
        self::assertNull($resolved);
    }

    public static function provideSingleInvalidFiles(): Iterator
    {
        yield ['foo'];
        yield ['root_file1'];
        yield ['01.yaml_only/'];
        yield ['01.yaml_only/file1.png.meta.yaml'];
        yield ['01.yaml_only'];
        yield ['04.with_children/_child1/'];
    }

    #[Test]
    public function parentDirectoryNotationLeavingBaseDirThrowsException(): void
    {
        $resolver = $this->getValidPagesResolver();

        $this->expectException(UnsafePathException::class);
        $resolver->get(new Path('foo/../../root_file1.png'));
    }

    /**
     * @param array<string> $foundFiles
     */
    #[DataProvider('provideMultipleFilePatterns')]
    #[Test]
    public function multipleFiles(string $pattern, ?string $inPath, array $foundFiles): void
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->find(new Path($pattern), $parent);
        self::assertEquals($foundFiles, array_keys($resolved), 'result matches when searching for '.$pattern);
    }

    public static function provideMultipleFilePatterns(): Iterator
    {
        yield [
            'file1.png',
            null,
            [
                '01.yaml_only/file1.png',
                'images/file1.png',
            ],
        ];
        yield [
            '/file1.png',
            null,
            [],
        ];
        yield [
            'file2.png',
            null,
            [
                '01.yaml_only/file2.png',
            ],
        ];
        yield [
            'file?.png',
            null,
            [
                '01.yaml_only/file1.png',
                '01.yaml_only/file2.png',
                '01.yaml_only/file3.png',
                'images/file1.png',
            ],
        ];
        yield [
            '01.yaml_only/file1.png',
            null,
            [
                '01.yaml_only/file1.png',
            ],
        ];
        yield [
            'root_file1.png',
            null,
            [
                'root_file1.png',
            ],
        ];
        yield [
            '*file1.png',
            null,
            [
                '01.yaml_only/file1.png',
                '04.with_children/_child1/child_file1.png',
                'images/file1.png',
                'root_file1.png',
            ],
        ];
        yield [
            '*file1.png',
            'images',
            [
                'images/file1.png',
            ],
        ];
        yield [
            '*file1.png',
            'images/',
            [
                'images/file1.png',
            ],
        ];
        yield [
            '#^[^/]*file1.png#',
            null,
            [
                'root_file1.png',
            ],
        ];
        yield [
            'child_file{3,4,5}.png',
            null,
            [
                '04.with_children/03.empty/child_file5.png',
                '04.with_children/_child1/child_file3.png',
                '04.with_children/_child1/child_file4.png',
            ],
        ];
        yield [
            'child_file{3,4,5}.png',
            '04.with_children',
            [
                '04.with_children/03.empty/child_file5.png',
                '04.with_children/_child1/child_file3.png',
                '04.with_children/_child1/child_file4.png',
            ],
        ];
        yield [
            '04.with_children/_child1/child_file{3,4,5}.png',
            null,
            [
                '04.with_children/_child1/child_file3.png',
                '04.with_children/_child1/child_file4.png',
            ],
        ];
        yield [
            '#04\\.with_children/_child1/child_file[3,4,5]{1}\\.png#',
            null,
            [
                '04.with_children/_child1/child_file3.png',
                '04.with_children/_child1/child_file4.png',
            ],
        ];
        yield [
            '../images/person_a.png',
            '01.yaml_only',
            [
                'images/person_a.png',
            ],
        ];
        yield [
            '../../images/person_?.png',
            '04.with_children/_child',
            [
                'images/person_a.png',
                'images/person_b.png',
                'images/person_c.png',
            ],
        ];
    }

    #[Test]
    public function singleFileWithNonStrictSearchReturnsDirectMatch(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('01.yaml_only/file1.png'));
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    #[Test]
    public function singleFileWithNonStrictSearchReturnsFirstFile(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('file1.png'));
        self::assertNull($resolved);

        $resolved = $resolver->findOne(new Path('file1.png'), null, false);
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    #[Test]
    public function singleFileWithNonStrictSearchReturnsNullIfNothingFound(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, false);
        self::assertNull($resolved);
    }

    #[Test]
    public function singleFileWithStrictSearchReturnsNullIfNothingFound(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, true);
        self::assertNull($resolved);
    }

    #[Test]
    public function singleFileWithStrictSearchReturnsResultIfExactlyOne(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('child_file8.png'), null, true);
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/04.with_children/03.empty/sub/dir/child_file8.png', $resolved->getPathname());
    }

    #[Test]
    public function singleFileWithStrictSearchExistingMoreThanOnceThrowsException(): void
    {
        $resolver = $this->getValidPagesResolver();

        $this->expectException(ResolverException::class);
        $resolver->findOne(new Path('file1.png'), null, true);
    }

    #[Test]
    public function singleFileSearchSupportsInPath(): void
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('child_file8.png'), new Path('04.with_children/03.empty'), true);
        self::assertInstanceOf(File::class, $resolved);
        self::assertSame('/04.with_children/03.empty/sub/dir/child_file8.png', $resolved->getPathname());
    }
}
