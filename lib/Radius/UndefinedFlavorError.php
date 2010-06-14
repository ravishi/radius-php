<?php

namespace Radius;

class UndefinedFlavorError extends Error
{
    public function __construct($tag, $stack)
    {
        parent::__construct(sprintf("internal error with unknown flavored tag %s and stack %s", $tag, $stack));
    }
}
