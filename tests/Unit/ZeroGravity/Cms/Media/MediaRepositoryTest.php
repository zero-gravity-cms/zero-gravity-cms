<?php

namespace Tests\Unit\ZeroGravity\Cms\Media;

use Iterator;
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
     *
     * @dataProvider provideValidMediaPaths
     */
    public function validPathReturnsFile($pathString): void
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        static::assertInstanceOf(File::class, $mediaRepository->getFile($pathString));
    }

    public function provideValidMediaPaths(): Iterator
    {
        yield ['01.yaml_only/file1.png'];
        yield ['01.yaml_only/file2.png'];
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidMediaPaths
     */
    public function invalidPathReturnsNull($pathString): void
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        static::assertNull($mediaRepository->getFile($pathString));
    }

    public function provideInvalidMediaPaths(): Iterator
    {
        yield ['01.yaml_only/file1.png.meta.yaml'];
        yield ['01.yaml_only/file4.png'];
    }
}
