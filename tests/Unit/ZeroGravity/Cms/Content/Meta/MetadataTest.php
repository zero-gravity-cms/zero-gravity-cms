<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\Metadata;

/**
 * @group meta
 */
class MetadataTest extends BaseUnit
{
    /**
     * @test
     */
    public function getAllReturnsAllElements()
    {
        $metaValues = [
            'a' => 'aa',
            'b' => 'bb',
        ];
        $meta = new Metadata($metaValues);

        static::assertSame($metaValues, $meta->getAll());
    }

    /**
     * @test
     */
    public function setAllReplacesAllElements()
    {
        $metaValues = [
            'a' => 'aa',
            'b' => 'bb',
        ];
        $meta = new Metadata($metaValues);

        $metaValues = [
            'c' => 'cc',
        ];
        $meta->setAll($metaValues);

        static::assertSame($metaValues, $meta->getAll());
    }

    /**
     * @test
     */
    public function getValueAllowsDefault()
    {
        $meta = new Metadata([]);
        static::assertSame('aa', $meta->getValue('a', 'aa'));
    }

    /**
     * @test
     */
    public function metadataSupportsArrayAccess()
    {
        $metaValues = [
            'a' => 'aa',
            'b' => 'bb',
        ];
        $meta = new Metadata($metaValues);

        static::assertSame('aa', $meta['a']);
        $meta['c'] = 'cc';
        static::assertSame('cc', $meta['c']);
        static::assertNull($meta['d']);
        static::assertArrayHasKey('a', $meta);
        unset($meta['a']);
        static::assertArrayNotHasKey('a', $meta);
    }
}
