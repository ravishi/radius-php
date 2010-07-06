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

    public function __construct($context, DelegatingOpenStruct $locals, $name, array $attr = array(), $block = null)
    {
        $this->context = $context;
        $this->locals = $locals;
        $this->name = $name;
        $this->attr = $attr;
        $this->block = $block;
    }

    /**
     * Return the value of a named attribute or the given $or value (default is NULL)
     * 
     * @param string $attr 
     * @param mixed $or 
     * @access public
     * @return mixed
     */
    public function getAttr($attr, $or = null)
    {
        return isset($this->attr[$attr]) ? $this->attr[$attr] : $or;
    }

    /**
     * Alias of $binding->attr
     * 
     * @access public
     * @return array an array of attributes in the $key => $value format
     */
    public function getAttrs()
    {
        return $this->attr;
    }

    /**
     * Expands the tag, returning its inner block rendered
     * 
     * @access public
     * @return NULL|mixed the inner block rendered or NULL if the tag is not double
     */
    public function expand()
    {
        return $this->isDouble() ? call_user_func($this->block) : null;
    }

    /**
     * @access public
     * @return bool
     */
    public function isSingle()
    {
        return $this->block === null;
    }

    /**
     * @access public
     * @return bool
     */
    public function isDouble()
    {
        return !$this->isSingle();
    }

    /**
     * Get the current nesting
     * 
     * @access public
     * @return string the current nesting
     */
    public function getNesting()
    {
        return $this->context->getCurrentNesting();
    }

    /**
     * Throws a UndefinedTagError error
     * 
     * @access public
     * @return void
     */
    public function missing()
    {
        $this->context->tagMissing($this->name, $this->attr, $this->block);
    }

    /**
     * Renders a tag
     * 
     * @param string $tag
     * @param array $attr
     * @param callback|NULL $block
     * @access public
     * @return mixed
     */
    public function render($tag, array $attr = array(), $block = null)
    {
        return $this->context->renderTag($tag, $attr, $block);
    }

    public function __get($key)
    {
        if ($key == 'attributes') $key = 'attr';

        if ($key == 'globals') {
            return $this->context->globals;
        } else if (in_array($key, array('context', 'locals', 'name', 'attr', 'block'))) {
            return $this->{$key};
        }

        throw new \Exception(sprintf("Property %s::%s does not exists", get_class($this), $key));
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
