<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Symfony\Component\Cache\Simple\ArrayCache;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Filesystem\FilesystemParser;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\PageResolver;

class PageResolverTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideSingleFilePaths
     *
     * @param string $path
     * @param string $inPath
     * @param string $expectedPath
     */
    public function singleFilesAreResolvedByPath(string $path, $inPath, string $expectedPath)
    {
        $resolver = $this->getPageResolver();
        $file = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        $this->assertInstanceOf(File::class, $file, "Page $path was found in $inPath");
        $this->assertSame($expectedPath, $file->getPathname());
    }

    public function provideSingleFilePaths()
    {
        return [
            [
                '/yaml_only/file2.png',
                null,
                '/01.yaml_only/file2.png',
            ],
            [
                'yaml_only/file2.png',
                null,
                '/01.yaml_only/file2.png',
            ],
            [
                'with_children/child1/child_file3.png',
                null,
                '/04.with_children/01.child1/child_file3.png',
            ],
            [
                'child1/child_file3.png',
                'with_children/',
                '/04.with_children/01.child1/child_file3.png',
            ],
            [
                'with_children/03.empty/sub/dir/child_file7.png',
                null,
                '/04.with_children/03.empty/sub/dir/child_file7.png',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNonExistingPagePaths
     *
     * @param string $path
     * @param string $inPath
     */
    public function singlePagesThatAreNotFound(string $path, $inPath)
    {
        $resolver = $this->getPageResolver();
        $pageFile = $resolver->get(new Path($path), null === $inPath ? null : new Path($inPath));

        $this->assertNull($pageFile);
    }

    public function provideNonExistingPagePaths()
    {
        return [
            [
                '01.yaml_only',
                null,
            ],
            [
                'yaml_only/file4.png',
                null,
            ],
            [
                '',
                null,
            ],
        ];
    }

    /**
     * @test
     * @skip
     * @dataProvider provideMultiplePagePatterns
     *
     * @param string    $pattern
     * @param Path|null $inPath
     * @param array     $foundFiles
     * @param array     $relativePaths
     */
    public function multiplePages(string $pattern, $inPath, array $foundFiles, array $relativePaths = [])
    {
        $resolver = $this->getPageResolver();

        $parent = (null !== $inPath) ? new Path($inPath) : null;
        $resolved = $resolver->find(new Path($pattern), $parent);

        $this->assertEquals($foundFiles, array_keys($resolved), 'result matches when searching for '.$pattern);

        if (!count($relativePaths)) {
            return;
        }

        foreach ($resolved as $relativePathName => $fileInfo) {
            $this->assertSame($relativePaths[$relativePathName], $fileInfo->getFileInfo()->getRelativePath());
        }
    }

    public function provideMultiplePagePatterns()
    {
        return [
            [
                'child?',
                null,
                [
                    '04.with_children/01.child1',
                    '04.with_children/02.child2',
                    '06.yaml_and_twig/01.child1',
                    '06.yaml_and_twig/02.child2',
                ],
                [
                    '04.with_children/01.child1' => '04.with_children',
                    '04.with_children/02.child2' => '04.with_children',
                    '06.yaml_and_twig/01.child1' => '06.yaml_and_twig',
                    '06.yaml_and_twig/02.child2' => '06.yaml_and_twig',
                ],
            ],
            [
                'markdown_only',
                null,
                [
                    '02.markdown_only',
                ],
            ],
            [
                '#^/[^/]+only$#',
                null,
                [
                    '01.yaml_only',
                    '02.markdown_only',
                    '05.twig_only',
                ],
            ],
            [
                '/child*',
                null,
                [],
                [],
            ],
            [
                'child2',
                null,
                [
                    '04.with_children/02.child2',
                    '06.yaml_and_twig/02.child2',
                ],
            ],
            [
                'child?',
                'yaml_and_twig',
                [
                    '06.yaml_and_twig/01.child1',
                    '06.yaml_and_twig/02.child2',
                ],
                [
                    '06.yaml_and_twig/01.child1' => '06.yaml_and_twig',
                    '06.yaml_and_twig/02.child2' => '06.yaml_and_twig',
                ],
            ],
        ];
    }

    /**
     * @return PageResolver
     */
    private function getPageResolver()
    {
        $fileFactory = $this->getDefaultFileFactory();
        $basePath = $fileFactory->getBasePath();
        $parser = new FilesystemParser($fileFactory, $basePath, false, []);
        $pageRepository = new ContentRepository($parser, new ArrayCache(), false);
        $resolver = new PageResolver($pageRepository, $basePath, $fileFactory);

        return $resolver;
    }
}
