<?php

namespace Radius;

class UndefinedTagError extends Error
{
    public function __construct($tag)
    {
        parent::__construct(sprintf("undefined tag `%s'", $tag));
    }
}
