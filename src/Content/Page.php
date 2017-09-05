<?php

namespace ZeroGravity\Cms\Content;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Path\Path;

class Page
{
    const SORTING_PREFIX_PATTERN = '/^[0-9]+\.(.*)/';

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $content;

    /**
     * @var Page[]
     */
    private $children = [];

    /**
     * @var Page
     */
    private $parent;

    /**
     * @var File[]
     */
    private $files = [];

    /**
     * @var Path
     */
    private $path;

    /**
     * @var Path
     */
    private $filesystemPath;

    /**
     * Create a new page object.
     *
     * @param string $name
     * @param array  $settings
     * @param Page   $parent
     */
    public function __construct(string $name, array $settings, Page $parent = null)
    {
        $this->name = $name;
        $this->settings = $this->parseSettings($settings);
        $this->parent = $parent;

        if (null !== $parent) {
            $parent->addChild($this);
        }
    }

    protected function parseSettings(array $settings)
    {
        if (isset($settings['published_at']) && !$settings['published_at'] instanceof DateTimeInterface) {
            $settings['published_at'] = new DateTimeImmutable($settings['published_at']);
        }

        return $settings;
    }

    /**
     * @param string|null $content
     */
    public function setContent(string $content = null): void
    {
        $this->content = $content;
    }

    /**
     * @param File[] $files
     */
    public function setFiles(array $files): void
    {
        Assert::allIsInstanceOf($files, File::class);
        $this->files = $files;
        $this->applyFileAliases();
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param string $filename
     *
     * @return null|File
     */
    public function getFile(string $filename): ? File
    {
        if (isset($this->files[$filename])) {
            return $this->files[$filename];
        }

        return null;
    }

    /**
     * Get all files for the given type.
     *
     * @param $type
     *
     * @return File[]
     */
    public function getFilesByType($type) : array
    {
        return array_filter($this->files, function (File $file) use ($type) {
            return $file->getType() === $type;
        });
    }

    /**
     * Get names/aliases for all available image files.
     *
     * @return File[]
     */
    public function getImages(): array
    {
        return $this->getFilesByType(FileTypeDetector::TYPE_IMAGE);
    }

    /**
     * Get names/aliases and paths for all available document files.
     *
     * @return File[]
     */
    public function getDocuments(): array
    {
        return $this->getFilesByType(FileTypeDetector::TYPE_DOCUMENT);
    }

    /**
     * Get path to single markdown file if available.
     *
     * @return File|null
     */
    public function getMarkdownFile(): ? File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_MARKDOWN);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * Get path to single YAML file if available.
     *
     * @return File|null
     */
    public function getYamlFile() : ? File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_YAML);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * Get path to single Twig file if available.
     *
     * @return File|null
     */
    public function getTwigFile() : ? File
    {
        $files = $this->getFilesByType(FileTypeDetector::TYPE_TWIG);
        if (count($files) > 0) {
            return current($files);
        }

        return null;
    }

    /**
     * @return Path
     */
    public function getPath() : Path
    {
        if (null === $this->path) {
            $this->buildPath();
        }

        return $this->path;
    }

    private function buildPath(): void
    {
        if (null === $this->parent) {
            $this->path = new Path('/'.$this->getSlug());
        } else {
            $this->path = new Path(rtrim($this->parent->getPath()->toString(), '/').'/'.$this->getSlug());
        }
    }

    /**
     * @return Page|null
     */
    public function getParent(): ? Page
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNameWithoutSortingPrefix(): string
    {
        if (preg_match(self::SORTING_PREFIX_PATTERN, $this->getName(), $matches)) {
            return $matches[1];
        }

        return $this->getName();
    }

    /**
     * @return Path
     */
    public function getFilesystemPath(): Path
    {
        if (null === $this->filesystemPath) {
            $this->buildFilesystemPath();
        }

        return $this->filesystemPath;
    }

    private function buildFilesystemPath(): void
    {
        if (null === $this->parent) {
            $this->filesystemPath = new Path('/'.$this->getName());
        } else {
            $this->filesystemPath = new Path(
                rtrim($this->parent->getFilesystemPath()->toString(), '/').'/'.$this->getName()
            );
        }
    }

    /**
     * @return string
     */
    public function getTitle(): ? string
    {
        return $this->settings['title'];
    }

    /**
     * @return string|null
     */
    public function getContent() : ? string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getSlug() : string
    {
        return (string) $this->settings['slug'];
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->settings['extra'];
    }

    /**
     * @return string
     */
    public function getMenuId(): string
    {
        return $this->settings['menu_id'];
    }

    /**
     * @return string|null
     */
    public function getMenuLabel(): ? string
    {
        if (!empty($this->settings['menu_label'])) {
            return $this->settings['menu_label'];
        }
        if (!empty($this->getTitle())) {
            return $this->getTitle();
        }

        return $this->getNameWithoutSortingPrefix();
    }

    /**
     * Page is listed in menus.
     *
     * @return bool
     */
    public function isVisible() : bool
    {
        return $this->settings['is_visible'];
    }

    /**
     * Page is considered a modular snippet, not a standalone page.
     *
     * @return bool
     */
    public function isModular(): bool
    {
        return $this->settings['is_modular'];
    }

    /**
     * Get custom template name to use for this page.
     *
     * @return string|null
     */
    public function getTemplate(): ? string
    {
        return $this->settings['template'];
    }

    /**
     * Get custom controller name to use for this page.
     *
     * @return string|null
     */
    public function getController() : ? string
    {
        return $this->settings['controller'];
    }

    /**
     * @param Page $childPage
     */
    public function addChild(Page $childPage) : void
    {
        $this->children[] = $childPage;
    }

    /**
     * @return Page[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Page is considered a modular snippet, not a standalone page.
     *
     * @return DateTimeInterface
     */
    public function getPublishedAt(): ? DateTimeInterface
    {
        return $this->settings['published_at'];
    }

    /**
     * @return bool
     */
    public function isPublished() : bool
    {
        return null === $this->getPublishedAt() || $this->getPublishedAt()->format('U') > time();
    }

    protected function applyFileAliases()
    {
        foreach ($this->settings['file_aliases'] as $from => $to) {
            if (isset($this->files[$to])) {
                $this->files[$from] = $this->files[$to];
            }
        }
    }

    /**
     * @param string $name
     * @param null   $default
     *
     * @return mixed
     */
    public function getExtraValue(string $name, $default = null)
    {
        if (array_key_exists($name, $this->settings['extra'])) {
            return $this->settings['extra'][$name];
        }

        return $default;
    }

    /**
     * Validate and resolve page settings.
     */
    public function validateSettings()
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->settings = $resolver->resolve($this->settings);
    }

    /**
     * Configure validation rules for page settings.
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'menu_label' => null,
            'menu_id' => 'default',
            'template' => null,
            'controller' => null,
            'title' => null,
            'extra' => [],
            'is_visible' => false,
            'is_modular' => false,
            'file_aliases' => [],
            'published_at' => null,
        ]);
        $resolver->setRequired([
            'slug',
        ]);
        $resolver->setAllowedTypes('extra', 'array');
        $resolver->setAllowedTypes('file_aliases', 'array');
        $resolver->setAllowedTypes('is_visible', 'bool');
        $resolver->setAllowedTypes('is_modular', 'bool');
        $resolver->setAllowedTypes('published_at', ['null', \DateTimeInterface::class]);
    }
}
