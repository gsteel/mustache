<?php

declare(strict_types=1);

class SectionIteratorObjects
{
    public $start = 'It worked the first time.';

    protected $_data = [
        ['item' => 'And it worked the second time.'],
        ['item' => 'As well as the third.'],
    ];

    public function middle()
    {
        return new ArrayIterator($this->_data);
    }

    public $final = 'Then, surprisingly, it worked the final time.';
}
