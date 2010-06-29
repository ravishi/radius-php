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

    public function defineTag($name, $callable)
    {
        $this->definitions[$name] = $callable;
    }

    public function defineTags(array $tags)
    {
        foreach ($tags as $name => $callable)
        {
            $this->defineTag($name, $callable);
        }
    }

    public function renderTag($name, $attr = array(), $block = null)
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
                return $this->tagMissing($name, $attr, $block);
            }
        }
    }

    public function tagMissing($name, $attr = array(), $block = null)
    {
        throw new Error\UndefinedTagError($name);
    }

    public function getCurrentNesting()
    {
        return join(':', $this->tagBindingStack);
    }

    private function stack($name, $attr = array(), $block, $call)
    {
        $previous = end($this->tagBindingStack);
        $previousLocals = $previous == null ? $this->globals : $previous->locals;

        $locals = new DelegatingOpenStruct($previousLocals);
        $binding = new TagBinding($this, $locals, $name, $attr, $block);

        array_push($this->tagBindingStack, $binding);
        $result = call_user_func($call, $binding);
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
