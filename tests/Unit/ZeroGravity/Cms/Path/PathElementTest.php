<?php

namespace Tests\Unit\ZeroGravity\Cms\Path;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\PathElement;

class PathElementTest extends BaseUnit
{
    #[Test]
    public function doubleColonElementIsRegardedAsParent(): void
    {
        $pathElement = new PathElement('foo');
        self::assertFalse($pathElement->isParentReference());

        $pathElement = new PathElement('..');
        self::assertTrue($pathElement->isParentReference());
    }

    #[Test]
    public function regexElementIsNeverRecognizedAsGlob(): void
    {
        $pathElement = new PathElement('/foo.*', true);
        self::assertTrue($pathElement->isGlob());
        self::assertFalse($pathElement->isRegex());

        $pathElement = new PathElement('/foo.*/', true);
        self::assertFalse($pathElement->isGlob());
        self::assertTrue($pathElement->isRegex());
    }
}
