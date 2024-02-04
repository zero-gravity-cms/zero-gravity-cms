<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;
use SplFileInfo;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\PageDiff;

class StructureException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param array<string, File> $files
     */
    public static function moreThanOneYamlFile(SplFileInfo $directory, array $files): self
    {
        return self::moreThanOneFileOfType($directory, $files, 'YAML');
    }

    /**
     * @param array<string, File> $files
     */
    public static function moreThanOneMarkdownFile(SplFileInfo $directory, array $files): self
    {
        return self::moreThanOneFileOfType($directory, $files, 'markdown');
    }

    public static function yamlAndMarkdownFilesMismatch(
        SplFileInfo $directory,
        File $yamlFile,
        File $markdownFile
    ): self {
        return new self(sprintf(
            'Directory %s contains a YAML and a markdown file, but the basenames do not match: %s vs %s',
            $directory->getRealPath(),
            $yamlFile->getFilename(),
            $markdownFile->getFilename()
        ));
    }

    public static function newPageNameAlreadyExists(PageDiff $diff): self
    {
        return new self(sprintf(
            'Cannot rename page "%s" to "%s" because a page with the same name already exists.',
            $diff->getOld()->getName(),
            $diff->getNewFilesystemPath()
        ));
    }

    /**
     * @param array<string, File> $files
     */
    private static function moreThanOneFileOfType(SplFileInfo $directory, array $files, string $type): self
    {
        $files = array_map(static fn (File $file): string => $file->getFilename(), $files);

        return new self(sprintf('There are %d %s files in directory %s: %s. Only 1 file per directory is supported',
            count($files),
            $type,
            $directory->getRealPath(),
            implode(', ', $files)
        ));
    }
}
