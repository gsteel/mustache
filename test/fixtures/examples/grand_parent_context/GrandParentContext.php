<?php

declare(strict_types=1);

class GrandParentContext
{
    public $grand_parent_id = 'grand_parent1';
    public $parent_contexts = [];

    public function __construct()
    {
        $this->parent_contexts[] = [
            'parent_id' => 'parent1',
            'child_contexts' => [
                ['child_id' => 'parent1-child1'],
                ['child_id' => 'parent1-child2'],
            ],
        ];

        $parent2 = new stdClass();
        $parent2->parent_id = 'parent2';
        $parent2->child_contexts = [
            ['child_id' => 'parent2-child1'],
            ['child_id' => 'parent2-child2'],
        ];

        $this->parent_contexts[] = $parent2;
    }
}
