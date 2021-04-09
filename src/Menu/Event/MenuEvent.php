<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class MenuEvent extends Event
{
    /**
     * @var string
     */
    protected $menuName;
    /**
     * @var ItemInterface
     */
    protected $rootItem;

    /**
     * @param string        $menuName
     * @param ItemInterface $rootItem
     */
    public function __construct(string $menuName, ItemInterface $rootItem)
    {
        $this->rootItem = $rootItem;
        $this->menuName = $menuName;
    }

    /**
     * @return ItemInterface
     */
    public function getRootItem(): ItemInterface
    {
        return $this->rootItem;
    }

    /**
     * @return string
     */
    public function getMenuName(): string
    {
        return $this->menuName;
    }
}
