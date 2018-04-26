<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Filesystem\WritableFilesystemPage;

class FilesystemException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param string $path
     *
     * @return static
     */
    public static function contentDirectoryDoesNotExist(string $path)
    {
        return new static(sprintf('Cannot parse filesystem. Page content directory "%s" does not exist', $path));
    }

    /**
     * @param PageDiff $diff
     *
     * @return static
     */
    public static function unsupportedWritablePageClass(PageDiff $diff)
    {
        return new static(sprintf(
            'FilesystemMapper can only handle PageDiffs containing "%s" instances. '.
            'Classes used in PageDiff are "%s" and "%s".',
            WritableFilesystemPage::class,
            get_class($diff->getOld()),
            get_class($diff->getNew())
        ));
    }

    /**
     * @param Page $page
     *
     * @return static
     */
    public static function cannotFindDirectoryForPage(Page $page)
    {
        return new static(sprintf(
            'Cannot find directory for Page instance with path "%s".',
            $page->getPath()->toString()
        ));
    }
}
