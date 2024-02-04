<?php

namespace Tests\Unit\ZeroGravity\Cms\Test;

use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Support\Helper\Unit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;
use ZeroGravity\Cms\Path\Resolver\FilesystemResolver;

use function PHPUnit\Framework\assertInstanceOf;

trait FixtureDirTrait
{
    /**
     * Get the directory containing the page fixture data.
     */
    protected function getPageFixtureDir(): string
    {
        return $this->getUnitHelperModule()->getPageFixtureDir();
    }

    protected function getUnitHelperModule(): Unit
    {
        $module = $this->getModule(Unit::class);
        assertInstanceOf(Unit::class, $module);

        return $module;
    }

    /**
     * Get the path to the directory containg valid page data.
     */
    protected function getValidPagesDir(): string
    {
        return $this->getPageFixtureDir().'/valid_pages';
    }

    /**
     * Get a FileFactory instance leading to the valid pages fixture dir.
     */
    public function getDefaultFileFactory(): FileFactory
    {
        return new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->getValidPagesDir());
    }

    protected function getValidPagesFilesystemMapper(): FilesystemMapper
    {
        $fileFactory = $this->getDefaultFileFactory();
        $path = $this->getValidPagesDir();

        return new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());
    }

    protected function getValidPagesResolver(): FilesystemResolver
    {
        return new FilesystemResolver($this->getDefaultFileFactory());
    }

    protected function getDefaultContentRepository(): ContentRepository
    {
        $fileFactory = $this->getDefaultFileFactory();
        $basePath = $fileFactory->getBasePath();
        $mapper = new FilesystemMapper($fileFactory, $basePath, false, [], new NullLogger(), new EventDispatcher());

        return new ContentRepository($mapper, new ArrayAdapter(), false);
    }
}
