<?php

namespace Tests\Unit\ZeroGravity\Cms\Test;

use Helper\Unit;
use Psr\Log\NullLogger;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Filesystem\FilesystemParser;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;
use ZeroGravity\Cms\Path\Resolver\FilesystemResolver;

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

    /**
     * @return FilesystemParser
     */
    protected function getValidPagesFilesystemParser()
    {
        $fileFactory = $this->getDefaultFileFactory();
        $path = $this->getValidPagesDir();

        return new FilesystemParser($fileFactory, $path, false, [], new NullLogger());
    }

    /**
     * @return FilesystemResolver
     */
    protected function getValidPagesResolver()
    {
        return new FilesystemResolver($this->getDefaultFileFactory());
    }
}
