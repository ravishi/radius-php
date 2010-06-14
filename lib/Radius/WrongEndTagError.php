<?php

namespace Radius;

class WrongEndTagError extends Error
{
    public function __construct($expectedTag, $gotTag, $stack = null)
    {
        parent::__construct(sprintf("wrong end tag `%s' found for start tag `%s'%s", $gotTag, $expectedTag,
            !is_null($stack) ? " with stack {$stack}" : null));
    }
}
