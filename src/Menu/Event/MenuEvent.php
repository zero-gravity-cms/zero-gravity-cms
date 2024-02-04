<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class MenuEvent extends Event
{
    protected ItemInterface $rootItem;

    public function __construct(
        protected readonly string $menuName,
        protected readonly ItemInterface $item,
        protected readonly ?ItemInterface $parentItem = null,
    ) {
        $this->rootItem = $this->resolveRoot($this->parentItem ?? $this->item);
    }

    public function getItem(): ItemInterface
    {
        return $this->item;
    }

    public function getParentItem(): ?ItemInterface
    {
        return $this->parentItem ?? $this->item->getParent();
    }

    public function getRootItem(): ItemInterface
    {
        return $this->rootItem;
    }

    public function getMenuName(): string
    {
        return $this->menuName;
    }

    protected function resolveRoot(ItemInterface $item): ItemInterface
    {
        if (!$item->getParent() instanceof ItemInterface) {
            return $item;
        }

        return $this->resolveRoot($item->getParent());
    }
}
