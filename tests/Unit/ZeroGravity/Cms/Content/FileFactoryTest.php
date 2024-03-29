<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

class FileFactoryTest extends BaseUnit
{
    #[Test]
    public function filesystemResolverThrowsExceptionIfGivenBasePathIsInvalid(): void
    {
        $this->expectException(FilesystemException::class);
        new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->getPageFixtureDir().'/invalid_path');
    }
}
