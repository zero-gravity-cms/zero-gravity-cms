<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Exception\ResolverException;
use ZeroGravity\Cms\Exception\UnsafePathException;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\FilesystemResolver;

class FilesystemResolverTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideSingleValidFiles
     *
     * @param string    $file
     * @param Path|null $inPath
     * @param string    $pathName
     */
    public function singleValidFile(string $file, $inPath, string $pathName)
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->get(new Path($file), $parent);
        $this->assertInstanceOf(File::class, $resolved, 'path results in file: '.$file);
        $this->assertSame($pathName, $resolved->getPathname(), 'pathname matches');
    }

    public function provideSingleValidFiles()
    {
        return [
            [
                '01.yaml_only/file1.png',
                null,
                '/01.yaml_only/file1.png',
            ],
            [
                '/01.yaml_only/file3.png',
                null,
                '/01.yaml_only/file3.png',
            ],
            [
                'root_file1.png',
                null,
                '/root_file1.png',
            ],
            [
                '/root_file2.png',
                null,
                '/root_file2.png',
            ],
            [
                '04.with_children/03.empty/sub/dir/child_file7.png',
                null,
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
            [
                'file1.png',
                '/01.yaml_only',
                '/01.yaml_only/file1.png',
            ],
            [
                'sub/dir/child_file7.png',
                '04.with_children/03.empty',
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
            [
                'sub/dir/child_file7.png',
                '/04.with_children/03.empty/',
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
        ];
    }

    /**
     * @test
     */
    public function parentDirectoryNotationIsNormalized()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('foo/bar/../../root_file1.png'));
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame('/root_file1.png', $resolved->getPathname(), 'pathname matches');
    }

    /**
     * @test
     */
    public function singleFileCanReachRelativePathOutsideInPath()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('../../images/person_a.png'), new Path('04.with_children/_child1'));
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame('/images/person_a.png', $resolved->getPathname(), 'pathname matches');
    }

    /**
     * @test
     * @dataProvider provideSingleInvalidFiles
     */
    public function singleInvalidFile($file)
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path($file));
        $this->assertNull($resolved);
    }

    public function provideSingleInvalidFiles()
    {
        return [
            ['foo'],
            ['root_file1'],
            ['01.yaml_only/'],
            ['01.yaml_only'],
            ['04.with_children/_child1/'],
        ];
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
     * @param string    $pattern
     * @param Path|null $inPath
     * @param array     $foundFiles
     */
    public function multipleFiles(string $pattern, $inPath, array $foundFiles)
    {
        $resolver = $this->getValidPagesResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->find(new Path($pattern), $parent);
        $this->assertEquals($foundFiles, array_keys($resolved), 'result matches when searching for '.$pattern);
    }

    public function provideMultipleFilePatterns()
    {
        return [
            [
                'file1.png',
                null,
                [
                    '01.yaml_only/file1.png',
                    'images/file1.png',
                ],
            ],
            [
                '/file1.png',
                null,
                [],
            ],
            [
                'file2.png',
                null,
                [
                    '01.yaml_only/file2.png',
                ],
            ],
            [
                'file?.png',
                null,
                [
                    '01.yaml_only/file1.png',
                    '01.yaml_only/file2.png',
                    '01.yaml_only/file3.png',
                    'images/file1.png',
                ],
            ],
            [
                '01.yaml_only/file1.png',
                null,
                [
                    '01.yaml_only/file1.png',
                ],
            ],
            [
                'root_file1.png',
                null,
                [
                    'root_file1.png',
                ],
            ],
            [
                '*file1.png',
                null,
                [
                    '01.yaml_only/file1.png',
                    '04.with_children/_child1/child_file1.png',
                    'images/file1.png',
                    'root_file1.png',
                ],
            ],
            [
                '*file1.png',
                'images',
                [
                    'images/file1.png',
                ],
            ],
            [
                '*file1.png',
                'images/',
                [
                    'images/file1.png',
                ],
            ],
            [
                '#^[^/]*file1.png#',
                null,
                [
                    'root_file1.png',
                ],
            ],
            [
                'child_file{3,4,5}.png',
                null,
                [
                    '04.with_children/03.empty/child_file5.png',
                    '04.with_children/_child1/child_file3.png',
                    '04.with_children/_child1/child_file4.png',
                ],
            ],
            [
                'child_file{3,4,5}.png',
                '04.with_children',
                [
                    '04.with_children/03.empty/child_file5.png',
                    '04.with_children/_child1/child_file3.png',
                    '04.with_children/_child1/child_file4.png',
                ],
            ],
            [
                '04.with_children/_child1/child_file{3,4,5}.png',
                null,
                [
                    '04.with_children/_child1/child_file3.png',
                    '04.with_children/_child1/child_file4.png',
                ],
            ],
            [
                '#04\\.with_children/_child1/child_file[3,4,5]{1}\\.png#',
                null,
                [
                    '04.with_children/_child1/child_file3.png',
                    '04.with_children/_child1/child_file4.png',
                ],
            ],
            [
                '../images/person_a.png',
                '01.yaml_only',
                [
                    'images/person_a.png',
                ],
            ],
            [
                '../../images/person_?.png',
                '04.with_children/_child',
                [
                    'images/person_a.png',
                    'images/person_b.png',
                    'images/person_c.png',
                ],
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
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    /**
     * @test
     */
    public function singleFileWithNonStrictSearchReturnsFirstFile()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->get(new Path('file1.png'));
        $this->assertNull($resolved);

        $resolved = $resolver->findOne(new Path('file1.png'), null, false);
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame('/01.yaml_only/file1.png', $resolved->getPathname());
    }

    /**
     * @test
     */
    public function singleFileWithNonStrictSearchReturnsNullIfNothingFound()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, false);
        $this->assertNull($resolved);
    }

    /**
     * @test
     */
    public function singleFileWithStrictSearchReturnsNullIfNothingFound()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('does_not_exist.png'), null, true);
        $this->assertNull($resolved);
    }

    /**
     * @test
     */
    public function singleFileWithStrictSearchReturnsResultIfExactlyOne()
    {
        $resolver = $this->getValidPagesResolver();

        $resolved = $resolver->findOne(new Path('child_file8.png'), null, true);
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame(
            '/04.with_children/03.empty/sub/dir/child_file8.png',
            $resolved->getPathname()
        );
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
        $this->assertInstanceOf(File::class, $resolved);
        $this->assertSame(
            '/04.with_children/03.empty/sub/dir/child_file8.png',
            $resolved->getPathname()
        );
    }

    /**
     * @return FilesystemResolver
     */
    private function getValidPagesResolver()
    {
        return new FilesystemResolver($this->getDefaultFileFactory());
    }
}
