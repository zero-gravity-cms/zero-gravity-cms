<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\Metadata;

#[Group('meta')]
class MetadataTest extends BaseUnit
{
    #[Test]
    public function getAllReturnsAllElements(): void
    {
        $metaValues = [
            'a' => 'aa',
            'b' => 'bb',
        ];
        $meta = new Metadata($metaValues);

        self::assertSame($metaValues, $meta->getAll());
    }

    #[Test]
    public function setAllReplacesAllElements(): void
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

        self::assertSame($metaValues, $meta->getAll());
    }

    #[Test]
    public function getValueAllowsDefault(): void
    {
        $meta = new Metadata([]);
        self::assertSame('aa', $meta->getValue('a', 'aa'));
    }

    #[Test]
    public function metadataSupportsArrayAccess(): void
    {
        $metaValues = [
            'a' => 'aa',
            'b' => 'bb',
        ];
        $meta = new Metadata($metaValues);

        self::assertSame('aa', $meta['a']);
        $meta['c'] = 'cc';
        self::assertSame('cc', $meta['c']);
        self::assertNull($meta['d']);
        self::assertArrayHasKey('a', $meta);
        unset($meta['a']);
        self::assertArrayNotHasKey('a', $meta);
    }
}
