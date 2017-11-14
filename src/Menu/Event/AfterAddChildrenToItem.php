<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;

class AfterAddChildrenToItem extends MenuEvent
{
    public const AFTER_ADD_CHILDREN_TO_ITEM = 'zerogravity.after_add_children_to_item';

    /**
     * @var ItemInterface
     */
    private $item;

    public function __construct(string $menuName, ItemInterface $item)
    {
        $root = $item;
        while (null !== $root->getParent()) {
            $root = $root->getParent();
        }

        parent::__construct($menuName, $root);

        $this->item = $item;
    }

    /**
     * @return ItemInterface
     */
    public function getItem(): ItemInterface
    {
        return $this->item;
    }
}
