<?php

namespace ZeroGravity\Cms\Content\Finder;

use Symfony\Component\Finder\Comparator;
use ZeroGravity\Cms\Content\Page;

trait PageFinderFilesTrait
{
    private $numFiles = [];
    private $numImages = [];
    private $numDocuments = [];

    /**
     * Adds tests for files count.
     *
     * Usage:
     *
     *   $finder->filesCount(2)      // Page contains exactly 2 files
     *   $finder->filesCount('>= 2') // Page contains at least 2 files
     *   $finder->filesCount('< 3')  // Page contains no more than 2 files
     *
     * @param string|int $numFiles The file count expression
     *
     * @return $this
     *
     * @see NumberComparator
     */
    public function numFiles($numFiles)
    {
        $this->numFiles[] = new Comparator\NumberComparator($numFiles);

        return $this;
    }

    /**
     * Adds tests for images count.
     *
     * Usage:
     *
     *   $finder->imagesCount(2)      // Page contains exactly 2 images
     *   $finder->imagesCount('>= 2') // Page contains at least 2 images
     *   $finder->imagesCount('< 3')  // Page contains no more than 2 images
     *
     * @param string|int $numImages The image count expression
     *
     * @return $this
     *
     * @see NumberComparator
     */
    public function numImages($numImages)
    {
        $this->numImages[] = new Comparator\NumberComparator($numImages);

        return $this;
    }

    /**
     * Adds tests for documents count.
     *
     * Usage:
     *
     *   $finder->documentsCount(2)      // Page contains exactly 2 documents
     *   $finder->documentsCount('>= 2') // Page contains at least 2 documents
     *   $finder->documentsCount('< 3')  // Page contains no more than 2 documents
     *
     * @param string|int $numDocuments The document count expression
     *
     * @return $this
     *
     * @see NumberComparator
     */
    public function numDocuments($numDocuments)
    {
        $this->numDocuments[] = new Comparator\NumberComparator($numDocuments);

        return $this;
    }

    /**
     * @param \Iterator $iterator
     *
     * @return \Iterator
     */
    private function applyNumberOfFilesIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->numFiles)) {
            $iterator = new Iterator\FileCountFilterIterator(
                $iterator,
                $this->numFiles,
                Iterator\FileCountFilterIterator::MODE_FILES
            );
        }

        return $iterator;
    }

    /**
     * @param \Iterator $iterator
     *
     * @return \Iterator
     */
    private function applyNumberOfImagesIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->numImages)) {
            $iterator = new Iterator\FileCountFilterIterator(
                $iterator,
                $this->numImages,
                Iterator\FileCountFilterIterator::MODE_IMAGES
            );
        }

        return $iterator;
    }

    /**
     * @param \Iterator $iterator
     *
     * @return \Iterator
     */
    private function applyNumberOfDocumentsIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->numDocuments)) {
            $iterator = new Iterator\FileCountFilterIterator(
                $iterator,
                $this->numDocuments,
                Iterator\FileCountFilterIterator::MODE_DOCUMENTS
            );
        }

        return $iterator;
    }
}
