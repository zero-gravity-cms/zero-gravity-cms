<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;

class FileTest extends BaseUnit
{
    /**
     * @test
     */
    public function fileHasVariousGetters()
    {
        $metadata = new \ZeroGravity\Cms\Content\Meta\Metadata([
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
    }

    /**
     * @test
     */
    public function pathnameAlwaysStartsWithSlash()
    {
        $file = new File('foo/bar/baz/photo.jpg', '', new \ZeroGravity\Cms\Content\Meta\Metadata([]), 'image');
        $this->assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
    }
}
