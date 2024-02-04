<?php

namespace Tests\Unit\ZeroGravity\Cms\Test;

use Symfony\Component\Filesystem\Filesystem;

trait TempDirTrait
{
    protected ?string $tempDir = null;

    /**
     * Create temporary directory and optionally mirror the content of the given directory.
     */
    protected function setupTempDir(string $copyDir = null): void
    {
        $this->tempDir = tempnam(sys_get_temp_dir(), 'zg_');

        $fs = new Filesystem();
        if (file_exists($this->tempDir)) {
            $fs->remove($this->tempDir);
        }

        if (null !== $copyDir) {
            $fs->mirror($copyDir, $this->tempDir);
        } else {
            $fs->mkdir($this->tempDir);
        }
    }

    /**
     * Remove temporary directory.
     */
    protected function cleanupTempDir(): void
    {
        if (null === $this->tempDir) {
            return;
        }

        (new Filesystem())->remove($this->tempDir);
    }
}
