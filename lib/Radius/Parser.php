<?php

namespace Radius;

class Parser
{
    private
        $stack,
        $current,
        $scanner,
        $tokens;

    public
        $context,
        $tag_prefix = 'r';

    public function __construct($context = null, array $options = array())
    {
        if (is_array($context) && empty($options))
        {
            $options = $context;
            $context = isset($options['context']) ? $options['context'] : null;
        }

        $this->context = $context instanceof Context ? $context : new Context;

        if (isset($options['tag_prefix'])) {
            $this->tag_prefix = $options['tag_prefix'];
        }

        $this->scanner = new Scanner;
    }

    public function parse($string)
    {
        $this->stack = array(new ParseContainerTag(null, array(), array(), function($t) {
            $ret = '';
            foreach ($t->contents as $c) {
                $ret .= is_object($c) ? $c->toString() : $c;
            }
            return $ret;
        }));
        $this->tokenize($string);
        $this->stackUp();
        return end($this->stack)->toString();
    }

    protected function tokenize($string)
    {
        $this->tokens = $this->scanner->operate($this->tag_prefix, $string);
    }

    protected function stackUp()
    {
        $context = $this->context;
        foreach ($this->tokens as $tok)
        {
            if (is_string($tok)) {
                end($this->stack)->pushContents($tok);
                continue;
            }
            switch ($tok['flavor']) {
            case 'open':
                $this->stack[] = new ParseContainerTag($tok['name'], $tok['attrs']);
                break;
            case 'self':
                end($this->stack)->pushContents(new ParseTag(function () use ($context, $tok) {
                    return $context->renderTag($tok['name'], $tok['attrs']);
                }));
                break;
            case 'close':
                $popped = array_pop($this->stack);
                if ($popped->name !== $tok['name']) {
                    throw new Error\WrongEndTagError($popped->name, $tok['name'], $this->stack);
                }
                $popped->onParse(function($with) use ($context, $popped) {
                    return $context->renderTag($popped->name, $popped->attributes, function() use ($with) {
                        $ret = '';
                        foreach ($with->contents as $c) {
                            $ret .= is_object($c)
                                ? $c->toString()
                                : $c;
                        }
                        return $ret;
                    });
                });
                end($this->stack)->pushContents($popped);
                break;
                // @codeCoverageIgnoreStart
            case 'tasteless':
                throw new Error\TastelessTagError($tok, $this->stack);
                break;
            default:
                throw new Error\UndefinedFlavorError($tok, $this->stack);
                break;
                // @codeCoverageIgnoreEnd
            }
        }

        if (count($this->stack) != 1) {
            throw new Error\MissingEndTagError(end($this->stack)->name, $this->stack);
        }
    }
}
