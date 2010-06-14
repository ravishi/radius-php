<?php

namespace Radius;

class ParseContainerTag extends ParseTag
{
    public
        $name,
        $attributes,
        $contents;

    public function __construct($name, array $attributes = array(), array $contents = array(), $block = null)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->contents = $contents;

        parent::__construct($block);
    }

    public function pushContents($content)
    {
        $this->contents[] = $content;
    }
}
