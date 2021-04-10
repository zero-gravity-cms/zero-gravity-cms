<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Iterator;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Exception\ResolverException;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\Path;

class FilesystemResolverTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideSingleValidFiles
     * @group resolver
     *
     * @param Path|null $inPath
     */
    public function singleValidFile(string $file, $inPath, string $pathName)
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->get(new Path($file), $parent);
        static::assertInstanceOf(File::class, $resolved, 'path results in file: '.$file);
        static::assertSame($pathName, $resolved->getPathname(), 'pathname matches');
    }

    public function provideSingleValidFiles(): Iterator
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

    /**
     * @test
     */
    public function parentDirectoryNotationIsNormalized()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('foo/bar/../../root_file1.png'));
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/root_file1.png', $resolved->getPathname(), 'pathname matches');
    }

    /**
     * @test
     */
    public function singleFileCanReachRelativePathOutsideInPath()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('../../images/person_a.png'), new Path('04.with_children/_child1'));
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/images/person_a.png', $resolved->getPathname(), 'pathname matches');
    }

    /**
     * @test
     * @dataProvider provideSingleInvalidFiles
     */
    public function singleInvalidFile($file)
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path($file));
        static::assertNull($resolved);
    }

    public function provideSingleInvalidFiles(): Iterator
    {
        yield ['foo'];
        yield ['root_file1'];
        yield ['01.yaml_only/'];
        yield ['01.yaml_only/file1.png.meta.yaml'];
        yield ['01.yaml_only'];
        yield ['04.with_children/_child1/'];
    }

    /**
     * @test
     */
    public function parentDirectoryNotationLeavingBaseDirThrowsException()
    {
        $resolver = $this->getValidPagesResolver();

        $this->expectException(UnsafePathException::class);
        $resolver->get(new Path('foo/../../root_file1.png'));
    }

    /**
     * @test
     * @dataProvider provideMultipleFilePatterns
     *
     * @param Path|null $inPath
     */
    public function multipleFiles(string $pattern, $inPath, array $foundFiles)
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->find(new Path($pattern), $parent);
        static::assertEquals($foundFiles, array_keys($resolved), 'result matches when searching for '.$pattern);
    }

    public function provideMultipleFilePatterns(): Iterator
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

    /**
     * @test
     */
    public function singleFileWithNonStrictSearchReturnsDirectMatch()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('01.yaml_only/file1.png'));
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    /**
     * @test
     */
    public function singleFileWithNonStrictSearchReturnsFirstFile()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('file1.png'));
        static::assertNull($resolved);

        $resolved = $resolver->findOne(new Path('file1.png'), null, false);
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    /**
     * @test
     */
    public function singleFileWithNonStrictSearchReturnsNullIfNothingFound()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, false);
        static::assertNull($resolved);
    }

    /**
     * @test
     */
    public function singleFileWithStrictSearchReturnsNullIfNothingFound()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, true);
        static::assertNull($resolved);
    }

    /**
     * @test
     */
    public function singleFileWithStrictSearchReturnsResultIfExactlyOne()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('child_file8.png'), null, true);
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/04.with_children/03.empty/sub/dir/child_file8.png', $resolved->getPathname());
    }

    /**
     * @test
     */
    public function singleFileWithStrictSearchExistingMoreThanOnceThrowsException()
    {
        $resolver = $this->getValidPagesResolver();

        $this->expectException(ResolverException::class);
        $resolver->findOne(new Path('file1.png'), null, true);
    }

    /**
     * @test
     */
    public function singleFileSearchSupportsInPath()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('child_file8.png'), new Path('04.with_children/03.empty'), true);
        static::assertInstanceOf(File::class, $resolved);
        static::assertSame('/04.with_children/03.empty/sub/dir/child_file8.png', $resolved->getPathname());
    }
}
