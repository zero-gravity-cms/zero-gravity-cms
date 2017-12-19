<?php

namespace Tests\Unit\ZeroGravity\Cms\Media;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Media\MediaRepository;

/**
 * @group media
 */
class MediaRepositoryTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideValidMediaPaths
     *
     * @param $pathString
     */
    public function validPathReturnsFile($pathString)
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        $this->assertInstanceOf(File::class, $mediaRepository->getFile($pathString));
    }

    public function provideValidMediaPaths()
    {
        return [
            ['01.yaml_only/file1.png'],
            ['01.yaml_only/file2.png'],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidMediaPaths
     *
     * @param $pathString
     */
    public function invalidPathReturnsNull($pathString)
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        $this->assertNull($mediaRepository->getFile($pathString));
    }

    public function provideInvalidMediaPaths()
    {
        return [
            ['01.yaml_only/file1.png.meta.yaml'],
            ['01.yaml_only/file4.png'],
        ];
    }
}
