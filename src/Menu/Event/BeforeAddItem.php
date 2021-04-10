<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;

final class BeforeAddItem extends MenuEvent
{
    private ItemInterface $parentItem;
    private ItemInterface $itemToBeAdded;

    public function __construct(string $menuName, ItemInterface $parentItem, ItemInterface $addedItem)
    {
        $root = $parentItem;
        while (null !== $root->getParent()) {
            $root = $root->getParent();
        }

        parent::__construct($menuName, $root);

        $this->parentItem = $parentItem;
        $this->itemToBeAdded = $addedItem;
    }

    public function getParentItem(): ItemInterface
    {
        return $this->parentItem;
    }

    public function getItemToBeAdded(): ItemInterface
    {
        return $this->itemToBeAdded;
    }
}
