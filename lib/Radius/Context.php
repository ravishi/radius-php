<?php

namespace Radius;

class Context
{
    protected
        $definitions,
        $globals,
        $tagBindingStack;

    public function __construct()
    {
        $this->definitions = array();
        $this->tagBindingStack = array();
        $this->globals = new DelegatingOpenStruct;
    }

    public function __get($key)
    {
        if ($key == 'globals') {
            return $this->globals;
        } 

        throw new \InvalidArgumentException(sprintf("Property %s::%s does not exists", get_class($this), $key));
    }

    /**
     * Define a tag
     * 
     * @param string $name 
     * @param callback $callback 
     * @access public
     * @return void
     */
    public function defineTag($name, $callback)
    {
        $this->definitions[$name] = $callback;
    }

    /**
     * A convenient method to define many tags at once
     * 
     * @param array $tags A $tagName => $callable array of tags
     * @access public
     * @return void
     */
    public function defineTags(array $tags)
    {
        foreach ($tags as $name => $callback)
        {
            $this->defineTag($name, $callback);
        }
    }

    /**
     * Render a tag
     * 
     * @param string $name 
     * @param array $attr An array of attributes
     * @param callback|NULL $block Inner block of expandable tags
     * @access public
     * @return mixed the rendered tag
     */
    public function renderTag($name, array $attr = array(), $block = null)
    {
        if (($pos = strpos($name, ':')) != 0)
        {
            $n1 = substr($name, 0, $pos);
            $n2 = substr($name, $pos + 1);
            $context = $this;
            return $this->renderTag($n1, array(), function() use ($context, $n2, $attr, $block) {
                return $context->renderTag($n2, $attr, $block);
            });
        }
        else
        {
            $qualifiedName = $this->qualifiedTagName($name);
            if (isset($this->definitions[$qualifiedName]))
            {
                return $this->stack($name, $attr, $block, $this->definitions[$qualifiedName]);
            }
            else
            {
                $this->tagMissing($name, $attr, $block);
            }
        }
    }

    /**
     * Throws a UndefinedTagError
     * 
     * @param string $name 
     * @param array $attr 
     * @param callable|NULL $block 
     * @access public
     * @return void
     */
    public function tagMissing($name, array $attr = array(), $block = null)
    {
        throw new Error\UndefinedTagError($name);
    }

    public function getCurrentNesting()
    {
        return join(':', $this->tagBindingStack);
    }

    protected function stack($name, $attr, $block, $callback)
    {
        $previous = end($this->tagBindingStack);
        $previousLocals = $previous == null ? $this->globals : $previous->locals;

        $locals = new DelegatingOpenStruct($previousLocals);
        $binding = new TagBinding($this, $locals, $name, $attr, $block);

        array_push($this->tagBindingStack, $binding);
        $result = call_user_func($callback, $binding);
        array_pop($this->tagBindingStack);

        return $result;
    }

    protected function qualifiedTagName($name)
    {
        $nestingParts = array();
        foreach ($this->tagBindingStack as $binding) {
            $nestingParts[] = $binding->name;
        }

        if (end($nestingParts) != $name) {
            $nestingParts[] = $name;
        }

        $specificName = join(':', $nestingParts);

        if (!isset($this->definitions[$specificName])) {
            $bestMatch = null;
            $bestAccuracy = -1;

            foreach (array_keys($this->definitions) as $definition) {
                if (preg_match('/(^|:)'.preg_quote($name, '/').'$/', $definition)
                    && $bestAccuracy < ($accuracy = $this->accuracy(explode(':', $definition), $nestingParts)))
                {
                    $bestMatch = $definition;
                    $bestAccuracy = $accuracy;
                }
            }
            return $bestMatch;
        }
        else {
            return $specificName;
        }
    }

    /**
     * Computes the numerical accuracy of a tag definition in comparsion with
     * a path of nesting parts
     * 
     * @param array $try 
     * @param array $path 
     * @access protected
     * @return int|false
     */
    protected function accuracy($try, $path)
    {
        $acc = 1000;

        while (!empty($try) && !empty($path))
        {
            if (end($try) == end($path))
            {
                array_pop($try);
                array_pop($path);
                continue;
            }

            array_pop($path);
            $acc--;
        }

        if (!empty($try))
            return false;
        else
            return $acc;
    }
}
