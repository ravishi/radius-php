<?php

namespace Radius;

class TagBinding implements \ArrayAccess
{
    protected
        $context,
        $locals,
        $name,
        $attr,
        $block;

    static protected
        $attrAccessor = array('context', 'locals', 'name', 'attr', 'block');

    public function __construct(Context $context, DelegatingOpenStruct $locals, $name, array $attr = array(), $block = null)
    {
        $this->context = $context;
        $this->locals = $locals;
        $this->name = $name;
        $this->attr = $attr;
        $this->block = $block;
    }

    public function getAttr($attr, $or = null)
    {
        return isset($this->attr[$attr]) ? $this->attr[$attr] : $or;
    }

    public function getAttrs()
    {
        return $this->attr;
    }

    public function expand()
    {
        return $this->isDouble() ? call_user_func($this->block) : null;
    }

    public function isSingle()
    {
        return $this->block === null;
    }

    public function isDouble()
    {
        return !$this->isSingle();
    }

    public function getNesting()
    {
        return $this->context->getCurrentNesting();
    }

    public function missing()
    {
        return $this->context->tagMissing($this->name, $this->attr, $this->block);
    }

    public function render($tag, array $args = array(), $block = null)
    {
        return $this->context->renderTag($tag, $args, $block);
    }

    public function __get($key)
    {
        if ($key == 'attributes') $key = 'attr';

        if ($key == 'globals') {
            return $this->context->getGlobals();
        } else if (in_array($key, self::$attrAccessor)) {
            return $this->{$key};
        }

        throw new \Exception(sprintf("tried to get an undefined property %s::%s", get_class($this), $key));
    }

    public function offsetExists($attr)
    {
        return isset($this->attr[$attr]);
    }

    public function offsetGet($attr)
    {
        return $this->getAttr($attr);
    }

    public function offsetSet($attr, $value)
    {
        $this->attr[$attr] = $value;
    }

    public function offsetUnset($attr)
    {
        unset($this->attr[$attr]);
    }

    public function __toString()
    {
        return $this->name;
    }
}
