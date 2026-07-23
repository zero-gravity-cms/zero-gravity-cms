<?php

declare(strict_types=1);

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\SettingValues;

#[Group('meta')]
class SettingValuesTest extends BaseUnit
{
    #[Test]
    public function constructorExposesValuesAsPublicProperties(): void
    {
        $values = $this->createSettingValues();

        self::assertSame('home', $values->slug);
        self::assertSame('page', $values->content_type);
        self::assertSame(['foo' => 'bar'], $values->extra);
        self::assertSame(['tag' => ['a', 'b']], $values->taxonomy);
        self::assertTrue($values->visible);
        self::assertInstanceOf(DateTimeImmutable::class, $values->date);
    }

    #[Test]
    public function offsetExistsReflectsKnownKeys(): void
    {
        $values = $this->createSettingValues();

        self::assertTrue($values->offsetExists('slug'));
        self::assertTrue($values->offsetExists('unpublish_date'));
        /* @phpstan-ignore-next-line */
        self::assertFalse($values->offsetExists('unknown'));
    }

    #[Test]
    public function offsetGetReturnsStoredValue(): void
    {
        $values = $this->createSettingValues();

        self::assertSame('home', $values['slug']);
        self::assertSame(['tag' => ['a', 'b']], $values['taxonomy']);
    }

    #[Test]
    public function offsetGetThrowsForUnknownKey(): void
    {
        $values = $this->createSettingValues();

        $this->expectException(OutOfBoundsException::class);
        /* @phpstan-ignore-next-line */
        $values->offsetGet('unknown');
    }

    #[Test]
    public function offsetSetIsNotSupported(): void
    {
        $values = $this->createSettingValues();

        $this->expectException(OutOfBoundsException::class);
        $values['slug'] = 'changed';
    }

    #[Test]
    public function offsetUnsetIsNotSupported(): void
    {
        $values = $this->createSettingValues();

        $this->expectException(OutOfBoundsException::class);
        unset($values['slug']);
    }

    #[Test]
    public function valueMatchesComparesScalarValues(): void
    {
        $values = $this->createSettingValues();

        self::assertTrue($values->valueMatches('slug', 'home'));
        self::assertFalse($values->valueMatches('slug', 'other'));
    }

    #[Test]
    public function valueMatchesComparesDatesByTheirSerializedValue(): void
    {
        $values = $this->createSettingValues();

        self::assertTrue($values->valueMatches('date', new DateTimeImmutable('2024-01-01 10:00:00')));
        self::assertFalse($values->valueMatches('date', new DateTimeImmutable('2024-01-02 10:00:00')));
    }

    #[Test]
    public function valueMatchesThrowsForUnknownKey(): void
    {
        $values = $this->createSettingValues();

        $this->expectException(OutOfBoundsException::class);
        /* @phpstan-ignore-next-line */
        $values->valueMatches('unknown', 'x');
    }

    #[Test]
    public function toArrayReturnsAllSettings(): void
    {
        $values = $this->createSettingValues();

        self::assertEquals([
            'child_defaults' => ['content_type' => 'article'],
            'content_template' => 'content.html.twig',
            'content_type' => 'page',
            'controller' => null,
            'date' => new DateTimeImmutable('2024-01-01 10:00:00'),
            'extra' => ['foo' => 'bar'],
            'file_aliases' => ['alias' => 'real.png'],
            'layout_template' => 'layout.html.twig',
            'menu_id' => 'zero-gravity',
            'menu_label' => 'Home',
            'modular' => false,
            'module' => false,
            'publish' => true,
            'publish_date' => new DateTimeImmutable('2024-02-01 00:00:00'),
            'slug' => 'home',
            'taxonomy' => ['tag' => ['a', 'b']],
            'title' => 'Home',
            'unpublish_date' => null,
            'visible' => true,
        ], $values->toArray());
    }

    private function createSettingValues(): SettingValues
    {
        return new SettingValues(
            child_defaults: ['content_type' => 'article'],
            content_template: 'content.html.twig',
            content_type: 'page',
            controller: null,
            date: new DateTimeImmutable('2024-01-01 10:00:00'),
            extra: ['foo' => 'bar'],
            file_aliases: ['alias' => 'real.png'],
            layout_template: 'layout.html.twig',
            menu_id: 'zero-gravity',
            menu_label: 'Home',
            modular: false,
            module: false,
            publish: true,
            publish_date: new DateTimeImmutable('2024-02-01 00:00:00'),
            slug: 'home',
            taxonomy: ['tag' => ['a', 'b']],
            title: 'Home',
            unpublish_date: null,
            visible: true,
        );
    }
}
