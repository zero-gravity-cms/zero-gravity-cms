<?php

namespace Tests\Unit\ZeroGravity\Cms\Path;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\PathElement;

class PathElementTest extends BaseUnit
{
    /**
     * @test
     */
    public function doubleColonElementIsRegardedAsParent()
    {
        $pathElement = new PathElement('foo');
        static::assertFalse($pathElement->isParentReference());

        $pathElement = new PathElement('..');
        static::assertTrue($pathElement->isParentReference());
    }

    /**
     * @test
     */
    public function regexElementIsNeverRecognizedAsGlob()
    {
        $pathElement = new PathElement('/foo.*', true);
        static::assertTrue($pathElement->isGlob());
        static::assertFalse($pathElement->isRegex());

        $pathElement = new PathElement('/foo.*/', true);
        static::assertFalse($pathElement->isGlob());
        static::assertTrue($pathElement->isRegex());
    }
}
