<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use SplFileInfo;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\PageDiff;

class StructureException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param SplFileInfo $directory
     * @param array       $files
     *
     * @return StructureException
     */
    public static function moreThanOneYamlFile(SplFileInfo $directory, array $files)
    {
        return static::moreThanOneFileOfType($directory, $files, 'YAML');
    }

    /**
     * @param SplFileInfo $directory
     * @param array       $files
     *
     * @return StructureException
     */
    public static function moreThanOneMarkdownFile(SplFileInfo $directory, array $files)
    {
        return static::moreThanOneFileOfType($directory, $files, 'markdown');
    }

    /**
     * @param SplFileInfo $directory
     * @param File        $yamlFile
     * @param File        $markdownFile
     *
     * @return StructureException
     */
    public static function yamlAndMarkdownFilesMismatch(
        SplFileInfo $directory,
        File $yamlFile,
        File $markdownFile
    ) {
        return new static(sprintf(
            'Directory %s contains a YAML and a markdown file, but the basenames do not match: %s vs %s',
            $directory->getRealPath(),
            $yamlFile->getFilename(),
            $markdownFile->getFilename()
        ));
    }

    /**
     * @param PageDiff $diff
     *
     * @return StructureException
     */
    public static function newPageNameAlreadyExists(PageDiff $diff)
    {
        return new static(sprintf(
            'Cannot rename page "%s" to "%s" because a page with the same name already exists.',
            $diff->getOld()->getName(),
            $diff->getNewName()
        ));
    }

    /**
     * @param SplFileInfo $directory
     * @param File[]      $files
     * @param string      $type
     *
     * @return static
     */
    protected static function moreThanOneFileOfType(SplFileInfo $directory, array $files, string $type)
    {
        $files = array_map(function (File $file) {
            return $file->getFilename();
        }, $files);

        return new static(sprintf('There are %d %s files in directory %s: %s. Only 1 file per directory is supported',
            count($files),
            $type,
            $directory->getRealPath(),
            implode(', ', $files)
        ));
    }
}
