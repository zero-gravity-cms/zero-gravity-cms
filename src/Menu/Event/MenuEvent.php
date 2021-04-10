<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class MenuEvent extends Event
{
    protected string $menuName;
    protected ItemInterface $rootItem;

    public function __construct(string $menuName, ItemInterface $rootItem)
    {
        $this->rootItem = $rootItem;
        $this->menuName = $menuName;
    }

    public function getRootItem(): ItemInterface
    {
        return $this->rootItem;
    }

    public function getMenuName(): string
    {
        return $this->menuName;
    }
}
