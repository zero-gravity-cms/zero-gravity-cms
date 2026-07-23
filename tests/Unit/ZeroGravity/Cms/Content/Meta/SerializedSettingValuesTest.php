<?php

declare(strict_types=1);

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\SerializedSettingValues;

#[Group('meta')]
class SerializedSettingValuesTest extends BaseUnit
{
    #[Test]
    public function constructorExposesValuesAsPublicProperties(): void
    {
        $values = $this->createSerializedSettingValues();

        self::assertSame('home', $values->slug);
        self::assertSame('2024-01-01 10:00:00', $values->date);
        self::assertSame(['foo' => 'bar'], $values->extra);
        self::assertSame(['tag' => ['a', 'b']], $values->taxonomy);
        self::assertTrue($values->visible);
    }

    #[Test]
    public function offsetExistsReflectsKnownKeys(): void
    {
        $values = $this->createSerializedSettingValues();

        self::assertTrue($values->offsetExists('slug'));
        self::assertTrue($values->offsetExists('date'));
        self::assertFalse($values->offsetExists('unknown'));
    }

    #[Test]
    public function offsetGetReturnsStoredValue(): void
    {
        $values = $this->createSerializedSettingValues();

        self::assertSame('home', $values['slug']);
        self::assertSame('2024-01-01 10:00:00', $values['date']);
    }

    #[Test]
    public function offsetGetThrowsForUnknownKey(): void
    {
        $values = $this->createSerializedSettingValues();

        $this->expectException(OutOfBoundsException::class);
        $values->offsetGet('unknown');
    }

    #[Test]
    public function offsetSetIsNotSupported(): void
    {
        $values = $this->createSerializedSettingValues();

        $this->expectException(OutOfBoundsException::class);
        $values['slug'] = 'changed';
    }

    #[Test]
    public function offsetUnsetIsNotSupported(): void
    {
        $values = $this->createSerializedSettingValues();

        $this->expectException(OutOfBoundsException::class);
        unset($values['slug']);
    }

    #[Test]
    public function valueMatchesComparesAlreadySerializedValues(): void
    {
        $values = $this->createSerializedSettingValues();

        self::assertTrue($values->valueMatches('date', '2024-01-01 10:00:00'));
        self::assertFalse($values->valueMatches('date', '2024-01-02 10:00:00'));
    }

    #[Test]
    public function valueMatchesThrowsForUnknownKey(): void
    {
        $values = $this->createSerializedSettingValues();

        $this->expectException(OutOfBoundsException::class);
        /* @phpstan-ignore-next-line */
        $values->valueMatches('unknown', 'x');
    }

    #[Test]
    public function toArrayReturnsAllSettings(): void
    {
        $values = $this->createSerializedSettingValues();

        self::assertSame([
            'child_defaults' => ['content_type' => 'article'],
            'content_template' => 'content.html.twig',
            'content_type' => 'page',
            'controller' => null,
            'date' => '2024-01-01 10:00:00',
            'extra' => ['foo' => 'bar'],
            'file_aliases' => ['alias' => 'real.png'],
            'layout_template' => 'layout.html.twig',
            'menu_id' => 'zero-gravity',
            'menu_label' => 'Home',
            'modular' => false,
            'module' => false,
            'publish' => true,
            'publish_date' => '2024-02-01 00:00:00',
            'slug' => 'home',
            'taxonomy' => ['tag' => ['a', 'b']],
            'title' => 'Home',
            'unpublish_date' => null,
            'visible' => true,
        ], $values->toArray());
    }

    private function createSerializedSettingValues(): SerializedSettingValues
    {
        return new SerializedSettingValues(
            child_defaults: ['content_type' => 'article'],
            content_template: 'content.html.twig',
            content_type: 'page',
            controller: null,
            date: '2024-01-01 10:00:00',
            extra: ['foo' => 'bar'],
            file_aliases: ['alias' => 'real.png'],
            layout_template: 'layout.html.twig',
            menu_id: 'zero-gravity',
            menu_label: 'Home',
            modular: false,
            module: false,
            publish: true,
            publish_date: '2024-02-01 00:00:00',
            slug: 'home',
            taxonomy: ['tag' => ['a', 'b']],
            title: 'Home',
            unpublish_date: null,
            visible: true,
        );
    }
}
