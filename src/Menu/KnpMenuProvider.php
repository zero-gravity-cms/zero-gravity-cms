<?php

namespace ZeroGravity\Cms\Menu;

use InvalidArgumentException;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Content\ReadablePageRepository;
use ZeroGravity\Cms\Exception\InvalidMenuNameException;
use ZeroGravity\Cms\Menu\Event\AfterAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\AfterAddItem;
use ZeroGravity\Cms\Menu\Event\AfterBuildMenu;
use ZeroGravity\Cms\Menu\Event\BeforeAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\BeforeAddItem;
use ZeroGravity\Cms\Menu\Event\BeforeBuildMenu;

class KnpMenuProvider implements MenuProviderInterface
{
    protected ReadablePageRepository $pageRepository;
    protected EventDispatcherInterface $eventDispatcher;
    protected FactoryInterface $factory;

    public function __construct(
        ReadablePageRepository $pageRepository,
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $factory
    ) {
        $this->pageRepository = $pageRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->factory = $factory;
    }

    /**
     * Retrieves a menu by its name.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException if the menu does not exists
     */
    public function get($name, array $options = []): ItemInterface
    {
        if (!$this->has($name)) {
            throw new InvalidMenuNameException($name);
        }

        return $this->buildMenu($name, $options);
    }

    /**
     * Checks whether a menu exists in this provider.
     *
     * @param string $name
     */
    public function has($name, array $options = []): bool
    {
        foreach ($this->pageRepository->getPageTree() as $page) {
            if ($this->pageHasItem($page, $name)) {
                return true;
            }
        }

        return false;
    }

    public function buildMenu(string $menuName, array $options = []): ItemInterface
    {
        $rootItem = $this->factory->createItem('root');

        $this->eventDispatcher->dispatch(new BeforeBuildMenu($menuName, $rootItem));

        foreach ($this->pageRepository->getPageTree() as $page) {
            $this->addPageItem($page, $rootItem, $menuName, $options);
        }

        $this->eventDispatcher->dispatch(new AfterBuildMenu($menuName, $rootItem));

        return $rootItem;
    }

    /**
     * Adds item to the given parent item if page should have an item.
     */
    protected function addPageItem(
        ReadablePage $page,
        ItemInterface $parent,
        string $menuName,
        array $defaultOptions
    ): void {
        if (!$this->pageHasItem($page, $menuName)) {
            return;
        }

        $pageItemSettings = $page->getExtra('menu_item_options', []);
        $itemOptions = array_merge(
            $defaultOptions,
            [
                'route' => $page->getPath()->toString(),
                'label' => $page->getMenuLabel(),
            ],
            $pageItemSettings
        );
        $itemOptions['extras'] = array_merge(
            $defaultOptions['extras'] ?? [],
            [
                'page_slug' => $page->getSlug(),
            ],
            $pageItemSettings['extras'] ?? []
        );
        $item = $this->factory->createItem($page->getName(), $itemOptions);

        $this->eventDispatcher->dispatch(new BeforeAddItem($menuName, $parent, $item));

        $parent->addChild($item);
        $this->eventDispatcher->dispatch(new BeforeAddChildrenToItem($menuName, $item));
        foreach ($page->getChildren() as $childPage) {
            $this->addPageItem($childPage, $item, $menuName, $defaultOptions);
        }
        $this->eventDispatcher->dispatch(new AfterAddChildrenToItem($menuName, $item));

        $this->eventDispatcher->dispatch(new AfterAddItem($menuName, $parent, $item));
    }

    protected function pageHasItem(ReadablePage $page, string $menuName): bool
    {
        return !$page->isModular() && $page->isVisible() && $page->getMenuId() === $menuName;
    }
}
