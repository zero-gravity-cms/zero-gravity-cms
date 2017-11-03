<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\InvalidMenuNameException;
use ZeroGravity\Cms\Filesystem\FilesystemParser;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;
use ZeroGravity\Cms\Menu\Event\AfterAddItem;
use ZeroGravity\Cms\Menu\Event\AfterBuildMenu;
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

        $rootItem = $provider->get('default');
        $this->assertInstanceOf(ItemInterface::class, $rootItem);
    }

    /**
     * @test
     */
    public function menuHasThreeItems()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('default');
        $this->assertCount(3, $rootItem->getChildren());
    }

    /**
     * @test
     */
    public function itemHasTwoChildren()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('default');
        $child = $rootItem->getChild('first-sibling');
        $this->assertCount(2, $child->getChildren());
    }

    /**
     * @test
     */
    public function itemHasCustomLabel()
    {
        $provider = $this->getProvider();

        $rootItem = $provider->get('default');
        $child = $rootItem->getChild('custom second sibling label');
        $this->assertInstanceOf(ItemInterface::class, $child);
    }

    /**
     * @test
     */
    public function itemUrisMatch()
    {
        $expectedItemUris = [
            'home' => '/home',
            'first-sibling' => '/first-sibling',
            'custom second sibling label' => '/second-sibling',
        ];
        $expectedChildItemUris = [
            'first-sibling' => [
                'first-child' => '/first-sibling/first-child',
                'second-child' => '/first-sibling/second-child',
            ],
        ];

        $provider = $this->getProvider();
        $rootItem = $provider->get('default');

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
     */
    public function eventsAreDispatchedDuringBuild()
    {
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $run = 0;

        $beforeBuildMenuCallback = function ($argument) {
            if (!$argument instanceof BeforeBuildMenu) {
                return false;
            }
            if ('default' !== $argument->getMenuName()) {
                return false;
            }
            if ('root' !== $argument->getRootItem()->getName()) {
                return false;
            }

            return true;
        };

        // menu start
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeBuildMenu::BEFORE_BUILD_MENU, $this->callback($beforeBuildMenuCallback))
        ;

        $beforeAddHomeItemCallback = function ($argument) {
            if (!$argument instanceof BeforeAddItem) {
                return false;
            }
            if ('root' !== $argument->getRootItem()->getName()) {
                return false;
            }
            if ('home' !== $argument->getItemToBeAdded()->getName()) {
                return false;
            }
            if ('root' !== $argument->getParentItem()->getName()) {
                return false;
            }

            return true;
        };

        // first item
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeAddItem::BEFORE_ADD_ITEM, $this->callback($beforeAddHomeItemCallback))
        ;
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterAddItem::AFTER_ADD_ITEM, $this->isInstanceOf(AfterAddItem::class))
        ;

        // second item
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeAddItem::BEFORE_ADD_ITEM, $this->isInstanceOf(BeforeAddItem::class))
        ;

        // second item first child
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeAddItem::BEFORE_ADD_ITEM, $this->isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterAddItem::AFTER_ADD_ITEM, $this->isInstanceOf(AfterAddItem::class))
        ;

        $afterAddSecondSubChildItemCallback = function ($argument) {
            if (!$argument instanceof AfterAddItem) {
                return false;
            }
            if ('root' !== $argument->getRootItem()->getName()) {
                return false;
            }
            if ('second-child' !== $argument->getAddedItem()->getName()) {
                return false;
            }
            if ('first-sibling' !== $argument->getParentItem()->getName()) {
                return false;
            }

            return true;
        };

        // second item second child
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeAddItem::BEFORE_ADD_ITEM, $this->isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterAddItem::AFTER_ADD_ITEM, $this->callback($afterAddSecondSubChildItemCallback))
        ;

        // second item finished
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterAddItem::AFTER_ADD_ITEM, $this->isInstanceOf(AfterAddItem::class))
        ;

        // third item
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(BeforeAddItem::BEFORE_ADD_ITEM, $this->isInstanceOf(BeforeAddItem::class))
        ;
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterAddItem::AFTER_ADD_ITEM, $this->isInstanceOf(AfterAddItem::class))
        ;

        // menu finished
        $dispatcher->expects($this->at($run++))
            ->method('dispatch')
            ->with(AfterBuildMenu::AFTER_BUILD_MENU, $this->isInstanceOf(AfterBuildMenu::class))
        ;

        $provider = $this->getProvider($dispatcher);
        $provider->get('default');
    }

    /**
     * @test
     */
    public function itemsCanBeChangedThroughEvents()
    {
        $dispatcher = new EventDispatcher();

        $provider = $this->getProvider($dispatcher);
        $provider->get('default');
    }

    protected function getRepository(): ContentRepository
    {
        $path = $this->getPageFixtureDir().'/sample_menu_pages';
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $parser = new FilesystemParser($fileFactory, $path, false, []);

        return new ContentRepository($parser, new ArrayCache(), false);
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
        $generator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $generator->expects($this->any())
            ->method('generate')
            ->willReturnArgument(0)
        ;

        return $generator;
    }
}
