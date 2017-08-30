<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;

class BeforeAddItem extends MenuEvent
{
    public const BEFORE_ADD_ITEM = 'zerogravity.before_add_item';

    /**
     * @var ItemInterface
     */
    private $parentItem;

    /**
     * @var ItemInterface
     */
    private $itemToBeAdded;

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

    /**
     * @return ItemInterface
     */
    public function getParentItem(): ItemInterface
    {
        return $this->parentItem;
    }

    /**
     * @return ItemInterface
     */
    public function getItemToBeAdded(): ItemInterface
    {
        return $this->itemToBeAdded;
    }
}
