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

        static::assertSame($metadata, $file->getMetadata());
        static::assertSame('image', $file->getType());
        static::assertSame('photo.jpg', $file->getFilename());
        static::assertSame('photo.jpg', $file->getBasename());
        static::assertSame('photo', $file->getBasename('.jpg'));
        static::assertSame('photo', $file->getDefaultBasename());
        static::assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
        static::assertSame('jpg', $file->getExtension());
        static::assertSame('/filesystem/path/foo/bar/baz/photo.jpg', $file->getFilesystemPathname());

        $file = new File('/foo/bar/baz/photo', '/filesystem/path/', $metadata, 'image');
        static::assertSame('photo', $file->getDefaultBasename());
    }

    /**
     * @test
     */
    public function pathnameAlwaysStartsWithSlash()
    {
        $file = new File('foo/bar/baz/photo.jpg', '', new Metadata([]), 'image');
        static::assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
    }

    /**
     * @test
     */
    public function toStringReturnsPathname()
    {
        $file = new File('/foo/bar/baz/photo.jpg', '/filesystem/path/', new Metadata([]), 'image');
        static::assertSame('/foo/bar/baz/photo.jpg', (string) $file);
    }
}
