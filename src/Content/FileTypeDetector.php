<?php

namespace ZeroGravity\Cms\Content;

class FileTypeDetector
{
    protected const EXTENSIONS = [
        FileTypes::TYPE_DOCUMENT => [
            'pdf',
            'docx',
            'xlsx',
            'txt',
        ],
        FileTypes::TYPE_IMAGE => [
            'jpg',
            'jpeg',
            'png',
            'gif',
        ],
        FileTypes::TYPE_MARKDOWN => [
            'md',
        ],
        FileTypes::TYPE_TWIG => [
            'twig',
        ],
        FileTypes::TYPE_YAML => [
            'yml',
            'yaml',
        ],
    ];

    protected array $extensionMap = [];

    public function __construct()
    {
        foreach (static::EXTENSIONS as $type => $extensions) {
            foreach ($extensions as $extension) {
                $this->extensionMap[$extension] = $type;
            }
        }
    }

    /**
     * This is being used to determine CMS usage of a given file.
     * It will NOT perform any mime detection or similar checks, since files are assumed to have been.
     *
     * @param string $filename File name or path
     */
    public function getType(string $filename): string
    {
        $ext = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $this->extensionMap[$ext] ?? FileTypes::TYPE_UNKNOWN;
    }
}
