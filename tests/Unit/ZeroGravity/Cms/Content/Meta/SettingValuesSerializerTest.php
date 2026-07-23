<?php

declare(strict_types=1);

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\SettingValues;
use ZeroGravity\Cms\Content\Meta\SettingValuesSerializer;

#[Group('meta')]
class SettingValuesSerializerTest extends BaseUnit
{
    #[Test]
    public function serializeConvertsAllSettingsToTheirPrimitiveForm(): void
    {
        $serialized = SettingValuesSerializer::serialize($this->createSettingValues());

        self::assertSame([
            'child_defaults' => [
                'date' => '2024-03-03 08:00:00',
                'nested' => ['deep_date' => '2024-04-04 09:00:00'],
            ],
            'content_template' => 'content.html.twig',
            'content_type' => 'page',
            'controller' => null,
            'date' => '2024-01-01 10:00:00',
            'extra' => [
                'plain' => 'value',
                'when' => '2024-05-05 11:00:00',
                'nested' => ['deep_when' => '2024-06-06 12:00:00'],
            ],
            'file_aliases' => ['alias' => 'real.png'],
            'layout_template' => null,
            'menu_id' => 'zero-gravity',
            'menu_label' => null,
            'modular' => false,
            'module' => false,
            'publish' => true,
            'publish_date' => '2024-02-01 00:00:00',
            'slug' => 'home',
            'taxonomy' => ['tag' => ['a', 'b']],
            'title' => 'Home',
            'unpublish_date' => null,
            'visible' => true,
        ], $serialized->toArray());
    }

    #[Test]
    public function serializeKeepsNullDatesAsNull(): void
    {
        $serialized = SettingValuesSerializer::serialize($this->createSettingValues(
            date: null,
            publishDate: null,
        ));

        self::assertNull($serialized->date);
        self::assertNull($serialized->publish_date);
        self::assertNull($serialized->unpublish_date);
    }

    #[Test]
    public function serializeValueReturnsScalarsUnchanged(): void
    {
        self::assertSame('text', SettingValuesSerializer::serializeValue('text'));
        self::assertSame(42, SettingValuesSerializer::serializeValue(42));
        self::assertSame(3.14, SettingValuesSerializer::serializeValue(3.14));
        self::assertTrue(SettingValuesSerializer::serializeValue(true));
        self::assertFalse(SettingValuesSerializer::serializeValue(false));
    }

    #[Test]
    public function serializeValueReturnsNullUnchanged(): void
    {
        self::assertNull(SettingValuesSerializer::serializeValue(null));
    }

    #[Test]
    public function serializeValueConvertsAnyDateToAString(): void
    {
        self::assertSame('2024-01-01 10:00:00', SettingValuesSerializer::serializeValue(new DateTimeImmutable('2024-01-01 10:00:00')));
        self::assertSame('2024-01-01 10:00:00', SettingValuesSerializer::serializeValue(new DateTime('2024-01-01 10:00:00')));
    }

    #[Test]
    public function serializeValueRecursivelySerializesArrays(): void
    {
        $result = SettingValuesSerializer::serializeValue([
            'string' => 'value',
            'int' => 7,
            'date' => new DateTimeImmutable('2024-01-01 10:00:00'),
            'nested' => [
                'deep_date' => new DateTimeImmutable('2024-02-02 12:00:00'),
                'deep_list' => [1, 2, 3],
            ],
        ]);

        self::assertSame([
            'string' => 'value',
            'int' => 7,
            'date' => '2024-01-01 10:00:00',
            'nested' => [
                'deep_date' => '2024-02-02 12:00:00',
                'deep_list' => [1, 2, 3],
            ],
        ], $result);
    }

    #[Test]
    public function serializeValueThrowsForNonSerializableObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SettingValuesSerializer::serializeValue(new stdClass());
    }

    private function createSettingValues(
        ?DateTimeImmutable $date = new DateTimeImmutable('2024-01-01 10:00:00'),
        ?DateTimeImmutable $publishDate = new DateTimeImmutable('2024-02-01 00:00:00'),
    ): SettingValues {
        return new SettingValues(
            child_defaults: [
                'date' => new DateTimeImmutable('2024-03-03 08:00:00'),
                'nested' => ['deep_date' => new DateTimeImmutable('2024-04-04 09:00:00')],
            ],
            content_template: 'content.html.twig',
            content_type: 'page',
            controller: null,
            date: $date,
            extra: [
                'plain' => 'value',
                'when' => new DateTimeImmutable('2024-05-05 11:00:00'),
                'nested' => ['deep_when' => new DateTimeImmutable('2024-06-06 12:00:00')],
            ],
            file_aliases: ['alias' => 'real.png'],
            layout_template: null,
            menu_id: 'zero-gravity',
            menu_label: null,
            modular: false,
            module: false,
            publish: true,
            publish_date: $publishDate,
            slug: 'home',
            taxonomy: ['tag' => ['a', 'b']],
            title: 'Home',
            unpublish_date: null,
            visible: true,
        );
    }
}
