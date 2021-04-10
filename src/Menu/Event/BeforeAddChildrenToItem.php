<?php

namespace ZeroGravity\Cms\Menu\Event;

use Knp\Menu\ItemInterface;

class BeforeAddChildrenToItem extends MenuEvent
{
    private ItemInterface $item;

    public function __construct(string $menuName, ItemInterface $item)
    {
        $root = $item;
        while (null !== $root->getParent()) {
            $root = $root->getParent();
        }

        parent::__construct($menuName, $root);

        $this->item = $item;
    }

    public function getItem(): ItemInterface
    {
        return $this->item;
    }
}
