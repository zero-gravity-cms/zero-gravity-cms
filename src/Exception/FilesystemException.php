<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Filesystem\Directory;
use ZeroGravity\Cms\Filesystem\WritableFilesystemPage;

class FilesystemException extends RuntimeException implements ZeroGravityException
{
    public static function contentDirectoryDoesNotExist(string $path): self
    {
        return new self(sprintf('Cannot parse filesystem. Page content directory "%s" does not exist', $path));
    }

    public static function unsupportedWritablePageClass(PageDiff $diff): self
    {
        return new self(sprintf(
            'FilesystemMapper can only handle PageDiffs containing "%s" instances. '.
            'Classes used in PageDiff are "%s" and "%s".',
            WritableFilesystemPage::class,
            $diff->getOld()::class,
            $diff->getNew()::class
        ));
    }

    public static function cannotFindDirectoryForPage(ReadablePage $page): self
    {
        return new self(sprintf(
            'Cannot find directory for Page instance with path "%s".',
            $page->getPath()->toString()
        ));
    }

    public static function missingMarkdownFile(Directory $directory): self
    {
        return new self(sprintf(
            'Directory %s does not contain the requested markdown file.',
            $directory->getPath()
        ));
    }
}
