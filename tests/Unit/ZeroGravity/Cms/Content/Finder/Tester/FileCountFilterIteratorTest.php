<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Finder\Tester;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Finder\Iterator\FileCountFilterIterator;
use ZeroGravity\Cms\Content\Page;

class FileCountFilterIteratorTest extends BaseUnit
{
    #[Test]
    public function invalidFileModeThrowsException(): void
    {
        $innerIterator = new ArrayIterator(['page' => new Page('page')]);
        $iterator = new FileCountFilterIterator($innerIterator, [], 'invalid-mode');

        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($iterator);
    }
}
