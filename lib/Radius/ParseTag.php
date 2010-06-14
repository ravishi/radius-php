<?php

namespace Radius;

class ParseTag
{
    protected $block;

    public function __construct($block)
    {
        $this->block = $block;
    }

    public function toString()
    {
        return call_user_func($this->block, $this);
    }

    public function onParse($block)
    {
        $this->block = $block;
    }
}
