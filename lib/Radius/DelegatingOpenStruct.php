<?php

namespace Radius;

class DelegatingOpenStruct
{
    protected
        $hash,
        $object;

    public function __construct(DelegatingOpenStruct $object = null)
    {
        $this->hash = array();
        $this->object = $object;
    }

    public function & __get($key)
    {
        if (isset($this->hash[$key])) {
            return $this->hash[$key];
        }
        else if ($this->object !== null && isset($this->object->{$key})) {
            return $this->object->__get($key);
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
        return isset($this->hash[$key]) ? true : isset($this->object->{$key});
    }

    public function __unset($key)
    {
        unset($this->hash[$key]);
    }
}
