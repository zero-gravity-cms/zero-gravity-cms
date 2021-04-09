<?php

namespace ZeroGravity\Cms\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\InvalidMenuNameException;
use ZeroGravity\Cms\Menu\Event\AfterAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\AfterAddItem;
use ZeroGravity\Cms\Menu\Event\AfterBuildMenu;
use ZeroGravity\Cms\Menu\Event\BeforeAddChildrenToItem;
use ZeroGravity\Cms\Menu\Event\BeforeAddItem;
use ZeroGravity\Cms\Menu\Event\BeforeBuildMenu;

class KnpMenuProvider implements MenuProviderInterface
{
    /**
     * @var ContentRepository
     */
    protected $contentRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * KnpMenuBuilder constructor.
     *
     * @param ContentRepository        $contentRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param FactoryInterface         $factory
     */
    public function __construct(
        ContentRepository $contentRepository,
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $factory
    ) {
        $this->contentRepository = $contentRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->factory = $factory;
    }

    /**
     * Retrieves a menu by its name.
     *
     * @param string $name
     * @param array  $options
     *
     * @return ItemInterface
     *
     * @throws \InvalidArgumentException if the menu does not exists
     */
    public function get($name, array $options = [])
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
     * @param array  $options
     *
     * @return bool
     */
    public function has($name, array $options = [])
    {
        foreach ($this->contentRepository->getPageTree() as $page) {
            if ($this->pageHasItem($page, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $menuName
     * @param array  $options
     *
     * @return ItemInterface
     */
    public function buildMenu(string $menuName, array $options = []): ItemInterface
    {
        $rootItem = $this->factory->createItem('root');

        $this->eventDispatcher->dispatch(new BeforeBuildMenu($menuName, $rootItem));

        foreach ($this->contentRepository->getPageTree() as $page) {
            $this->addPageItem($page, $rootItem, $menuName, $options);
        }

        $this->eventDispatcher->dispatch(new AfterBuildMenu($menuName, $rootItem));

        return $rootItem;
    }

    /**
     * Adds item to the given parent item if page should have an item.
     *
     * @param Page          $page
     * @param ItemInterface $parent
     * @param string        $menuName
     * @param array         $defaultOptions
     */
    protected function addPageItem(Page $page, ItemInterface $parent, string $menuName, array $defaultOptions): void
    {
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
            isset($defaultOptions['extras']) ? $defaultOptions['extras'] : [],
            [
                'page_slug' => $page->getSlug(),
            ],
            isset($pageItemSettings['extras']) ? $pageItemSettings['extras'] : []
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

    /**
     * @param Page $page
     * @param      $menuName
     *
     * @return bool
     */
    protected function pageHasItem(Page $page, $menuName): bool
    {
        return !$page->isModular() && $page->isVisible() && $page->getMenuId() === $menuName;
    }
}
