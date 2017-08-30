<?php

namespace Tests\Unit\ZeroGravity\Cms\Media;

use Symfony\Component\Cache\Simple\ArrayCache;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Filesystem\FilesystemParser;

/**
 * @tag current
 */
class MediaRepositoryTest extends BaseUnit
{
    /**
     * @var ContentRepository
     */
    protected $repository;

    protected function _before()
    {
        $this->prepareRepository();
    }

    /**
     * @test
     * @dataProvider provideValidMediaPaths
     */
    public function mediaCanBeLoaded($path)
    {
    }

    public function provideValidMediaPaths()
    {
        return [
            ['01.yaml_only/1.png'],
        ];
    }

    protected function prepareRepository()
    {
        $fileFactory = $this->getDefaultFileFactory();
        $path = $this->getValidPagesDir();
        $parser = new FilesystemParser($fileFactory, $path, false, []);

        $this->repository = new ContentRepository($parser, new ArrayCache(), false);
    }
}
