<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

class FileFactoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function FilesystemResolverThrowsExceptionIfGivenBasePathIsInvalid()
    {
        $this->expectException(FilesystemException::class);
        new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->getPageFixtureDir().'/invalid_path');
    }
}
