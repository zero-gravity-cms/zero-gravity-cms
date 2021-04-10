<?php

namespace ZeroGravity\Cms\Content;

class FileTypeDetector
{
    public const EXTENSIONS = [
        'yaml' => ['yml', 'yaml'],
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'markdown' => ['md'],
        'twig' => ['twig'],
        'document' => ['pdf', 'docx', 'xlsx', 'txt'],
    ];

    public const TYPE_YAML = 'yaml';
    public const TYPE_IMAGE = 'image';
    public const TYPE_MARKDOWN = 'markdown';
    public const TYPE_TWIG = 'twig';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_UNKNOWN = 'unknown';

    protected array $extensionMap = [];

    public function __construct()
    {
        foreach (self::EXTENSIONS as $type => $extensions) {
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

        return $this->extensionMap[$ext] ?? self::TYPE_UNKNOWN;
    }
}
