<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;

class AfterAddItem extends MenuEvent
{
    /**
     * @var ItemInterface
     */
    private $parentItem;

    /**
     * @var ItemInterface
     */
    private $addedItem;

    public function __construct(string $menuName, ItemInterface $parentItem, ItemInterface $addedItem)
    {
        $root = $parentItem;
        while (null !== $root->getParent()) {
            $root = $root->getParent();
        }

        parent::__construct($menuName, $root);

        $this->parentItem = $parentItem;
        $this->addedItem = $addedItem;
    }

    public function getParentItem(): ItemInterface
    {
        return $this->parentItem;
    }

    public function getAddedItem(): ItemInterface
    {
        return $this->addedItem;
    }
}
