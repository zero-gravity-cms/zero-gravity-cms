<?php

namespace Tests\Unit\ZeroGravity\Cms\Media;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Media\MediaRepository;
use ZeroGravity\Cms\Path\Path;

#[Group('media')]
class MediaRepositoryTest extends BaseUnit
{
    #[DataProvider('provideValidMediaPaths')]
    #[Test]
    public function validPathReturnsFile(Path|string $pathString): void
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        self::assertInstanceOf(File::class, $mediaRepository->getFile($pathString));
    }

    public static function provideValidMediaPaths(): Iterator
    {
        yield ['01.yaml_only/file1.png'];
        yield ['01.yaml_only/file2.png'];
    }

    #[DataProvider('provideInvalidMediaPaths')]
    #[Test]
    public function invalidPathReturnsNull(Path|string $pathString): void
    {
        $mediaRepository = new MediaRepository($this->getValidPagesResolver());

        self::assertNull($mediaRepository->getFile($pathString));
    }

    public static function provideInvalidMediaPaths(): Iterator
    {
        yield ['01.yaml_only/file1.png.meta.yaml'];
        yield ['01.yaml_only/file4.png'];
    }
}
