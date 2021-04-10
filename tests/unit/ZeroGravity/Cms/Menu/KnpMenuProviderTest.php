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

        $this->assertFalse($provider->has('invalid-menu-name'));

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
        $this->assertInstanceOf(ItemInterface::class, $rootItem);
    }

    /**
     * @test
     */
    public function menuHasThreeItems()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $this->assertCount(3, $rootItem->getChildren());
    }

    /**
     * @test
     */
    public function itemHasTwoChildren()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        $this->assertCount(2, $child->getChildren());
    }

    /**
     * @test
     */
    public function itemContainsPageSlug()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('02.first-sibling');
        $this->assertSame('first-sibling', $child->getExtra('page_slug'));
    }

    /**
     * @test
     */
    public function itemHasCustomLabel()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        $this->assertSame('custom second sibling label', $child->getLabel());
    }

    /**
     * @test
     */
    public function itemHasCustomExtraValue()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('zero-gravity');
        $child = $rootItem->getChild('03.second-sibling');
        $this->assertSame('custom_value', $child->getExtra('custom_extra'));
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
            $this->assertInstanceOf(ItemInterface::class, $child);
            $this->assertSame($uri, $child->getUri());

            if (isset($expectedChildItemUris[$childName])) {
                foreach ($expectedChildItemUris[$childName] as $subChildName => $subUri) {
                    $subChild = $child->getChild($subChildName);
                    $this->assertInstanceOf(ItemInterface::class, $subChild);
                    $this->assertSame($subUri, $subChild->getUri());
                }
            }
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function eventsAreDispatchedDuringBuild()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $run = 0;

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

        // menu start
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($beforeBuildMenuCallback))
        ;

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

        // first item
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($beforeAddHomeItemCallback))
        ;

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

        // first item's children pre-event
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($beforeAddHomeChildrenCallback))
        ;

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

        // first item's children post-event
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($afterAddHomeChildrenCallback))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddItem::class))
        ;

        // second item
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddChildrenToItem::class))
        ;

        // second item first child
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddItem::class))
        ;

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

        // second item second child
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($afterAddSecondSubChildItemCallback))
        ;

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

        // second item finished
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::callback($afterAddSecondItemChildrenCallback))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddItem::class))
        ;

        // third item
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(BeforeAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddChildrenToItem::class))
        ;
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterAddItem::class))
        ;

        // menu finished
        $dispatcher->expects(self::at($run++))
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterBuildMenu::class))
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
        $generator->expects($this->any())
            ->method('generate')
            ->willReturnArgument(0)
        ;

        return $generator;
    }
}
