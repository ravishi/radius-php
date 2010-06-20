<?php

namespace Radius\Error;

class TastelessTagError extends Error
{
    public function __construct($tag, $stack)
    {
        parent::__construct(sprintf("internal error with tasteless tag %s and stack %s", $tag, $stack));
    }
}
