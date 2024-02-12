<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu;

use Codeception\Attribute\Group;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\InvalidMenuNameException;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;
use ZeroGravity\Cms\Menu\Event\AfterAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\AfterAddItem;
use ZeroGravity\Cms\Menu\Event\AfterBuildMenu;
use ZeroGravity\Cms\Menu\Event\BeforeAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\BeforeAddItem;
use ZeroGravity\Cms\Menu\Event\BeforeBuildMenu;
use ZeroGravity\Cms\Menu\KnpMenuProvider;

#[Group('menu')]
class KnpMenuProviderTest extends BaseUnit
{
    #[Test]
    public function menuProviderThrowsExceptionIfMenuDoesNotExist(): void
    {
        $provider = $this->getProvider();

        self::assertFalse($provider->has('invalid-menu-name'));

        $this->expectException(InvalidMenuNameException::class);
        $provider->get('invalid-menu-name');
    }

    #[Test]
    public function menuProviderReturnsMenuIfExists(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        self::assertInstanceOf(ItemInterface::class, $rootItem);
    }

    #[Test]
    public function menuHasThreeItems(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        self::assertCount(3, $rootItem->getChildren());
    }

    #[Test]
    public function itemHasTwoChildren(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        self::assertCount(2, $child->getChildren());
    }

    #[Test]
    public function itemContainsPageSlug(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        self::assertSame('first-sibling', $child->getExtra('page_slug'));
    }

    #[Test]
    public function itemHasCustomLabel(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        self::assertSame('custom second sibling label', $child->getLabel());
    }

    #[Test]
    public function itemHasCustomExtraValue(): void
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        self::assertSame('custom_value', $child->getExtra('custom_extra'));
    }

    #[Test]
    public function itemUrisMatch(): void
    {
        $expectedItemUris = [
            '01.home' => '/home',
            '02.first-sibling' => '/first-sibling',
            '03.second-sibling' => '/second-sibling',
        ];
        $expectedChildItemUris = [
            '02.first-sibling' => [
                '01.first-child' => '/first-sibling/first-child',
                '02.second-child' => '/first-sibling/second-child',
            ],
        ];

        $provider = $this->getProvider();
        $rootItem = $provider->get('zero-gravity');

        foreach ($expectedItemUris as $childName => $uri) {
            $child = $rootItem->getChild($childName);
            self::assertInstanceOf(ItemInterface::class, $child);
            self::assertSame($uri, $child->getUri());

            if (isset($expectedChildItemUris[$childName])) {
                foreach ($expectedChildItemUris[$childName] as $subChildName => $subUri) {
                    $subChild = $child->getChild($subChildName);
                    self::assertInstanceOf(ItemInterface::class, $subChild);
                    self::assertSame($subUri, $subChild->getUri());
                }
            }
        }
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function eventsAreDispatchedDuringBuild(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $step = 0;
        $callbackChain = static function ($argument) use (&$step): bool {
            ++$step;
            $beforeBuildMenuCallback = static function ($argument): bool {
                if (!$argument instanceof BeforeBuildMenu) {
                    return false;
                }
                if ('zero-gravity' !== $argument->getMenuName()) {
                    return false;
                }

                return 'root' === $argument->getRootItem()->getName();
            };
            $beforeAddHomeItemCallback = static function ($argument): bool {
                if (!$argument instanceof BeforeAddItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('Home' !== $argument->getItem()->getLabel()) {
                    return false;
                }

                return 'root' === $argument->getParentItem()->getName();
            };
            $beforeAddHomeChildrenCallback = static function ($argument): bool {
                if (!$argument instanceof BeforeAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('01.home' !== $argument->getItem()->getName()) {
                    return false;
                }

                return 'Home' === $argument->getItem()->getLabel();
            };
            $afterAddHomeChildrenCallback = static function ($argument): bool {
                if (!$argument instanceof AfterAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('01.home' !== $argument->getItem()->getName()) {
                    return false;
                }

                return 'Home' === $argument->getItem()->getLabel();
            };
            $afterAddSecondSubChildItemCallback = static function ($argument): bool {
                if (!$argument instanceof AfterAddItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('Second Child' !== $argument->getItem()->getLabel()) {
                    return false;
                }

                return 'First Sibling' === $argument->getParentItem()->getLabel();
            };
            $afterAddSecondItemChildrenCallback = static function ($argument): bool {
                if (!$argument instanceof AfterAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('First Sibling' !== $argument->getItem()->getLabel()) {
                    return false;
                }

                return 2 === count($argument->getItem()->getChildren());
            };

            return match ($step) {
                1 => $beforeBuildMenuCallback($argument),
                2 => $beforeAddHomeItemCallback($argument),
                3 => $beforeAddHomeChildrenCallback($argument),
                4 => $afterAddHomeChildrenCallback($argument),
                5 => $argument instanceof AfterAddItem,
                6 => $argument instanceof BeforeAddItem,
                7 => $argument instanceof BeforeAddChildrenToItem,
                8 => $argument instanceof BeforeAddItem,
                9 => $argument instanceof BeforeAddChildrenToItem,
                10 => $argument instanceof AfterAddChildrenToItem,
                11 => $argument instanceof AfterAddItem,
                12 => $argument instanceof BeforeAddItem,
                13 => $argument instanceof BeforeAddChildrenToItem,
                14 => $argument instanceof AfterAddChildrenToItem,
                15 => $afterAddSecondSubChildItemCallback($argument),
                16 => $afterAddSecondItemChildrenCallback($argument),
                17 => $argument instanceof AfterAddItem,
                18 => $argument instanceof BeforeAddItem,
                19 => $argument instanceof BeforeAddChildrenToItem,
                20 => $argument instanceof AfterAddChildrenToItem,
                21 => $argument instanceof AfterAddItem,
                22 => $argument instanceof AfterBuildMenu,
                default => true,
            };
        };

        $dispatcher->expects($this->atLeast(15))
            ->method('dispatch')
            ->with(self::callback($callbackChain))
        ;

        $provider = $this->getProvider($dispatcher);
        $provider->get('zero-gravity');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function itemsCanBeChangedThroughEvents(): void
    {
        $dispatcher = new EventDispatcher();

        $provider = $this->getProvider($dispatcher);
        $provider->get('zero-gravity');
    }

    protected function getRepository(): ContentRepository
    {
        $path = $this->getPageFixtureDir().'/sample_menu_pages';
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        return new ContentRepository($mapper, new ArrayAdapter(), false);
    }

    protected function getProvider(EventDispatcherInterface $dispatcher = null): KnpMenuProvider
    {
        if (!$dispatcher instanceof EventDispatcherInterface) {
            $dispatcher = new EventDispatcher();
        }
        $factory = $this->getMenuFactory();

        return new KnpMenuProvider($this->getRepository(), $dispatcher, $factory);
    }

    protected function getMenuFactory(): FactoryInterface
    {
        $factory = new MenuFactory();
        $extension = new RoutingExtension($this->getMockUrlGenerator());
        $factory->addExtension($extension);

        return $factory;
    }

    protected function getMockUrlGenerator(): UrlGeneratorInterface
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator
            ->method('generate')
            ->willReturnArgument(0)
        ;

        return $generator;
    }
}
