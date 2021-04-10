<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\Meta\Metadata;

class FileTest extends BaseUnit
{
    /**
     * @test
     */
    public function fileHasVariousGetters()
    {
        $metadata = new Metadata([
            'alt_text' => 'Sample alt text',
        ]);

        $file = new File('/foo/bar/baz/photo.jpg', '/filesystem/path/', $metadata, 'image');

        $this->assertSame($metadata, $file->getMetadata());
        $this->assertSame('image', $file->getType());
        $this->assertSame('photo.jpg', $file->getFilename());
        $this->assertSame('photo.jpg', $file->getBasename());
        $this->assertSame('photo', $file->getBasename('.jpg'));
        $this->assertSame('photo', $file->getDefaultBasename());
        $this->assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
        $this->assertSame('jpg', $file->getExtension());
        $this->assertSame('/filesystem/path/foo/bar/baz/photo.jpg', $file->getFilesystemPathname());

        $file = new File('/foo/bar/baz/photo', '/filesystem/path/', $metadata, 'image');
        $this->assertSame('photo', $file->getDefaultBasename());
    }

    /**
     * @test
     */
    public function pathnameAlwaysStartsWithSlash()
    {
        $file = new File('foo/bar/baz/photo.jpg', '', new Metadata([]), 'image');
        $this->assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
    }

    /**
     * @test
     */
    public function toStringReturnsPathname()
    {
        $file = new File('/foo/bar/baz/photo.jpg', '/filesystem/path/', new Metadata([]), 'image');
        $this->assertSame('/foo/bar/baz/photo.jpg', (string) $file);
    }
}
