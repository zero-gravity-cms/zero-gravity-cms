<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use ZeroGravity\Cms\Content\Finder\Iterator\FileCountFilterIterator;
use ZeroGravity\Cms\Content\ReadablePage;

trait PageFinderFilesTrait
{
    /**
     * @var list<NumberComparator>
     */
    private array $numFiles = [];
    /**
     * @var list<NumberComparator>
     */
    private array $numImages = [];
    /**
     * @var list<NumberComparator>
     */
    private array $numDocuments = [];

    /**
     * Adds tests for files count.
     *
     * Usage:
     *
     *   $finder->filesCount('== 2')      // Page contains exactly 2 files
     *   $finder->filesCount('>= 2') // Page contains at least 2 files
     *   $finder->filesCount('< 3')  // Page contains no more than 2 files
     *
     * @param string|null $numFiles The file count expression
     *
     * @see NumberComparator
     */
    public function numFiles(?string $numFiles): self
    {
        $this->numFiles[] = new NumberComparator($numFiles);

        return $this;
    }

    /**
     * Adds tests for images count.
     *
     * Usage:
     *
     *   $finder->imagesCount('== 2')      // Page contains exactly 2 images
     *   $finder->imagesCount('>= 2') // Page contains at least 2 images
     *   $finder->imagesCount('< 3')  // Page contains no more than 2 images
     *
     * @param string|null $numImages The image count expression
     *
     * @see NumberComparator
     */
    public function numImages(?string $numImages): self
    {
        $this->numImages[] = new NumberComparator($numImages);

        return $this;
    }

    /**
     * Adds tests for documents count.
     *
     * Usage:
     *
     *   $finder->documentsCount('== 2')      // Page contains exactly 2 documents
     *   $finder->documentsCount('>= 2') // Page contains at least 2 documents
     *   $finder->documentsCount('< 3')  // Page contains no more than 2 documents
     *
     * @param string|null $numDocuments The document count expression
     *
     * @see NumberComparator
     */
    public function numDocuments(?string $numDocuments): self
    {
        $this->numDocuments[] = new NumberComparator($numDocuments);

        return $this;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyNumberOfFilesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->numFiles)) {
            return new FileCountFilterIterator(
                $iterator,
                $this->numFiles,
                FileCountFilterIterator::MODE_FILES
            );
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyNumberOfImagesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->numImages)) {
            return new FileCountFilterIterator(
                $iterator,
                $this->numImages,
                FileCountFilterIterator::MODE_IMAGES
            );
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyNumberOfDocumentsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->numDocuments)) {
            return new FileCountFilterIterator(
                $iterator,
                $this->numDocuments,
                FileCountFilterIterator::MODE_DOCUMENTS
            );
        }

        return $iterator;
    }
}
