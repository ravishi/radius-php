<?php

require __DIR__.'/../lib/Radius/Autoloader.php';

Radius\Autoloader::register();

use Radius\Context;
use Radius\Parser;

class ParserTest
{
    protected
        $context,
        $parser;

    public function __construct()
    {
        $this->context = new Context;

        $this->context->defineTags(array(
            'echo'          => function($t) { return $t->getAttr('value'); },
            'wrap'          => function($t) { return "[{$t->expand()}]"; },
            'reverse'       => function($t) { return strrev($t->expand()); },
            'capitalize'    => function($t) { return strtoupper($t->expand()); },
            'attr_export'   => function($t) { return var_export($t->getAttrs(), true); },
        ));

        $this->parser = new Parser($this->context);
    }

    public function test()
    {
        $this->context->defineTags(array(
            'test'  => function ($t) { return "Hello {$t->expand()}!"; },
            'hello' => function ($t) { return $t->render('test', array(), function () use ($t) { return $t->expand(); }); },
        ));
        
        $this->assertParseOutput('Hello John!', '<r:hello>John</r:hello>');
    }

    protected function assertParseOutput($output, $input, $message = null)
    {
        $r = $this->parser->parse($input);
        echo "(
            \$input: `{$input}'
            \$expected: `{$output}'
            \$output: `{$r}'
        )";
    }
}

$test = new ParserTest;
$test->test();
