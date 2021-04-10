<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
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

/**
 * @group menu
 */
class KnpMenuProviderTest extends BaseUnit
{
    /**
     * @test
     */
    public function menuProviderThrowsExceptionIfMenuDoesNotExist()
    {
        $provider = $this->getProvider();

        static::assertFalse($provider->has('invalid-menu-name'));

        $this->expectException(InvalidMenuNameException::class);
        $provider->get('invalid-menu-name');
    }

    /**
     * @test
     */
    public function menuProviderReturnsMenuIfExists()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        static::assertInstanceOf(ItemInterface::class, $rootItem);
    }

    /**
     * @test
     */
    public function menuHasThreeItems()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        static::assertCount(3, $rootItem->getChildren());
    }

    /**
     * @test
     */
    public function itemHasTwoChildren()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        static::assertCount(2, $child->getChildren());
    }

    /**
     * @test
     */
    public function itemContainsPageSlug()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        static::assertSame('first-sibling', $child->getExtra('page_slug'));
    }

    /**
     * @test
     */
    public function itemHasCustomLabel()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        static::assertSame('custom second sibling label', $child->getLabel());
    }

    /**
     * @test
     */
    public function itemHasCustomExtraValue()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        static::assertSame('custom_value', $child->getExtra('custom_extra'));
    }

    /**
     * @test
     */
    public function itemUrisMatch()
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
            static::assertInstanceOf(ItemInterface::class, $child);
            static::assertSame($uri, $child->getUri());

            if (isset($expectedChildItemUris[$childName])) {
                foreach ($expectedChildItemUris[$childName] as $subChildName => $subUri) {
                    $subChild = $child->getChild($subChildName);
                    static::assertInstanceOf(ItemInterface::class, $subChild);
                    static::assertSame($subUri, $subChild->getUri());
                }
            }
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @group now
     */
    public function eventsAreDispatchedDuringBuild()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $step = 0;
        $callbackChain = function ($argument) use (&$step) {
            ++$step;

            $beforeBuildMenuCallback = function ($argument) {
                if (!$argument instanceof BeforeBuildMenu) {
                    return false;
                }
                if ('zero-gravity' !== $argument->getMenuName()) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }

                return true;
            };
            $beforeAddHomeItemCallback = function ($argument) {
                if (!$argument instanceof BeforeAddItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('Home' !== $argument->getItemToBeAdded()->getLabel()) {
                    return false;
                }
                if ('root' !== $argument->getParentItem()->getName()) {
                    return false;
                }

                return true;
            };
            $beforeAddHomeChildrenCallback = function ($argument) {
                if (!$argument instanceof BeforeAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('01.home' !== $argument->getItem()->getName()) {
                    return false;
                }
                if ('Home' !== $argument->getItem()->getLabel()) {
                    return false;
                }

                return true;
            };
            $afterAddHomeChildrenCallback = function ($argument) {
                if (!$argument instanceof AfterAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('01.home' !== $argument->getItem()->getName()) {
                    return false;
                }
                if ('Home' !== $argument->getItem()->getLabel()) {
                    return false;
                }

                return true;
            };
            $afterAddSecondSubChildItemCallback = function ($argument) {
                if (!$argument instanceof AfterAddItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('Second Child' !== $argument->getAddedItem()->getLabel()) {
                    return false;
                }
                if ('First Sibling' !== $argument->getParentItem()->getLabel()) {
                    return false;
                }

                return true;
            };
            $afterAddSecondItemChildrenCallback = function ($argument) {
                if (!$argument instanceof AfterAddChildrenToItem) {
                    return false;
                }
                if ('root' !== $argument->getRootItem()->getName()) {
                    return false;
                }
                if ('First Sibling' !== $argument->getItem()->getLabel()) {
                    return false;
                }
                if (2 !== count($argument->getItem()->getChildren())) {
                    return false;
                }

                return true;
            };

            switch ($step) {
                case 1:
                    return $beforeBuildMenuCallback($argument);
                case 2:
                    return $beforeAddHomeItemCallback($argument);
                case 3:
                    return $beforeAddHomeChildrenCallback($argument);

                // first item's children post-event
                case 4:
                    return $afterAddHomeChildrenCallback($argument);
                case 5:
                    return $argument instanceof AfterAddItem;

                // second item
                case 6:
                    return $argument instanceof BeforeAddItem;
                case 7:
                    return $argument instanceof BeforeAddChildrenToItem;

                // second item first child
                case 8:
                    return $argument instanceof BeforeAddItem;
                case 9:
                    return $argument instanceof BeforeAddChildrenToItem;
                case 10:
                    return $argument instanceof AfterAddChildrenToItem;
                case 11:
                    return $argument instanceof AfterAddItem;

                // second item second child
                case 12:
                    return $argument instanceof BeforeAddItem;
                case 13:
                    return $argument instanceof BeforeAddChildrenToItem;
                case 14:
                    return $argument instanceof AfterAddChildrenToItem;
                case 15:
                    return $afterAddSecondSubChildItemCallback($argument);

                // second item finished
                case 16:
                    return $afterAddSecondItemChildrenCallback($argument);
                case 17:
                    return $argument instanceof AfterAddItem;

                // third item
                case 18:
                    return $argument instanceof BeforeAddItem;
                case 19:
                    return $argument instanceof BeforeAddChildrenToItem;
                case 20:
                    return $argument instanceof AfterAddChildrenToItem;
                case 21:
                    return $argument instanceof AfterAddItem;

                // menu finished
                case 22:
                    return $argument instanceof AfterBuildMenu;
            }

            return true;
        };

        $dispatcher->expects(self::atLeast(15))
            ->method('dispatch')
            ->with(self::callback($callbackChain))
        ;

        $provider = $this->getProvider($dispatcher);
        $provider->get('zero-gravity');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function itemsCanBeChangedThroughEvents()
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
        if (null === $dispatcher) {
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
