<?php

declare(strict_types=1);

class SectionMagicObjects
{
    public $start = 'It worked the first time.';

    public function middle()
    {
        return new MagicObject();
    }

    public $final = 'Then, surprisingly, it worked the final time.';
}

class MagicObject
{
    protected $_data = [
        'foo' => 'And it worked the second time.',
        'bar' => 'As well as the third.',
    ];

    public function __get($key)
    {
        return $this->_data[$key] ?? null;
    }

    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }
}
