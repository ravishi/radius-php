<?php

namespace Radius;

/**
 * PONDER: qual a diferença de não retornar __get por referência?
 */
class DelegatingOpenStruct
{
    private
        $hash,
        $parent;

    public function __construct(DelegatingOpenStruct $parent = null)
    {
        $this->hash = array();
        $this->parent = $parent;
    }

    public function & __get($key)
    {
        if (isset($this->hash[$key])) {
            return $this->hash[$key];
        }
        else if ($this->parent !== null && isset($this->parent->{$key})) {
            return $this->parent->__get($key);
        }
        else {
            $this->hash[$key] = null;
            return $this->hash[$key];
        }
    }

    public function __set($key, $value)
    {
        $this->hash[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->hash[$key]) ? true : isset($this->parent->{$key});
    }

    public function __unset($key)
    {
        unset($this->hash[$key]);
    }
}
