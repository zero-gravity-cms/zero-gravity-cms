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
        $this->assertFalse($pathElement->isParentReference());

        $pathElement = new PathElement('..');
        $this->assertTrue($pathElement->isParentReference());
    }

    /**
     * @test
     */
    public function regexElementIsNeverRecognizedAsGlob()
    {
        $pathElement = new PathElement('/foo.*', true);
        $this->assertTrue($pathElement->isGlob());
        $this->assertFalse($pathElement->isRegex());

        $pathElement = new PathElement('/foo.*/', true);
        $this->assertFalse($pathElement->isGlob());
        $this->assertTrue($pathElement->isRegex());
    }
}
