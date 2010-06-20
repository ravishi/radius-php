<?php

namespace Radius\Error;

class MissingEndTagError extends Error
{
    public function __construct($tagName, $stack = null)
    {
        parent::__construct(sprintf("end tag not found for start tag `%s'%s", $tagName,
            !is_null($stack) ? " with stack {$stack}" : null));
    }
}
