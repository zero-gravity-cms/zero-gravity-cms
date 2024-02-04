<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\Meta\Metadata;

class FileTest extends BaseUnit
{
    #[Test]
    public function fileHasVariousGetters(): void
    {
        $metadata = new Metadata([
            'alt_text' => 'Sample alt text',
        ]);

        $file = new File('/foo/bar/baz/photo.jpg', '/filesystem/path/', $metadata, 'image');

        self::assertSame($metadata, $file->getMetadata());
        self::assertSame('image', $file->getType());
        self::assertSame('photo.jpg', $file->getFilename());
        self::assertSame('photo.jpg', $file->getBasename());
        self::assertSame('photo', $file->getBasename('.jpg'));
        self::assertSame('photo', $file->getDefaultBasename());
        self::assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
        self::assertSame('jpg', $file->getExtension());
        self::assertSame('/filesystem/path/foo/bar/baz/photo.jpg', $file->getFilesystemPathname());

        $file = new File('/foo/bar/baz/photo', '/filesystem/path/', $metadata, 'image');
        self::assertSame('photo', $file->getDefaultBasename());
    }

    #[Test]
    public function pathnameAlwaysStartsWithSlash(): void
    {
        $file = new File('foo/bar/baz/photo.jpg', '', new Metadata([]), 'image');
        self::assertSame('/foo/bar/baz/photo.jpg', $file->getPathname());
    }

    #[Test]
    public function toStringReturnsPathname(): void
    {
        $file = new File('/foo/bar/baz/photo.jpg', '/filesystem/path/', new Metadata([]), 'image');
        self::assertSame('/foo/bar/baz/photo.jpg', (string) $file);
    }
}
