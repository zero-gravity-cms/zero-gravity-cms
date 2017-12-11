<?php

namespace Tests\Unit\ZeroGravity\Cms\Test;

use Helper\Unit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

trait FixtureDirTrait
{
    /**
     * Get the directory containing the page fixture data.
     *
     * @return string
     */
    protected function getPageFixtureDir(): string
    {
        return $this->getUnitHelperModule()->getPageFixtureDir();
    }

    /**
     * @return Unit
     */
    protected function getUnitHelperModule()
    {
        return $this->getModule('\Helper\Unit');
    }

    /**
     * Get the path to the directory containg valid page data.
     *
     * @return string
     */
    protected function getValidPagesDir(): string
    {
        return $this->getPageFixtureDir().'/valid_pages';
    }

    /**
     * Get a FileFactory instance leading to the valid pages fixture dir.
     *
     * @return FileFactory
     */
    public function getDefaultFileFactory(): FileFactory
    {
        return new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->getValidPagesDir());
    }
}
