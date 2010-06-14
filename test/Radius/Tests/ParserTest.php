<?php

namespace Radius\Tests;

use Radius\Context;
use Radius\Parser;

class TestContext extends Context
{
}

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected
        $context,
        $parser;

    public function setUp()
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

    public function testInitializationWithOptions()
    {
        $this->parser = new Parser(new TestContext);
        $this->assertType(__NAMESPACE__.'\\TestContext', $this->parser->context);

        $this->parser = new Parser(array('context' => new TestContext));
        $this->assertType(__NAMESPACE__.'\\TestContext', $this->parser->context);

        $this->parser = new Parser(array('tag_prefix' => 'radius'));
        $this->assertType('Radius\\Context', $this->parser->context);
        $this->assertEquals($this->parser->tag_prefix, 'radius');

        $this->parser = new Parser(new TestContext, array('tag_prefix' => 'radius'));
        $this->assertType(__NAMESPACE__.'\\TestContext', $this->parser->context);
        $this->assertEquals($this->parser->tag_prefix, 'radius');

        $this->parser = new Parser(array('context' => new TestContext, 'tag_prefix' => 'radius'));
        $this->assertType(__NAMESPACE__.'\\TestContext', $this->parser->context);
        $this->assertEquals($this->parser->tag_prefix, 'radius');
    }

    public function testParseIndividualTagsAndParameters()
    {
        $this->parser->context->defineTag('add', function($t) {
            return $t->getAttr('a', 0) + $t->getAttr('b', 0);
        });

        $this->assertParseOutput('<3>', '<<r:add a="1" b="2" />>');
    }

    public function testParseAttributes()
    {
        $attr = array(
            'attr1' => '1',
            'attr2' => '2',
            'attr3' => '3',
            'attr4' => '4',
        );
        $attrStr = $this->attrFromArray($attr);

        $this->assertParseOutput(var_export($attr, true), "<r:attr_export $attrStr />", $attrStr);
    }

    public function testParseAttributesWithSlashesAndBrackets()
    {
        $attr = array(
            'slash' => '/',
            'angle' => '>',
        );
        $str = $this->attrFromArray($attr);

        $this->assertParseOutput(var_export($attr, true), "<r:attr_export $str />", $str);
    }

    public function testParseQuotes()
    {
        $this->assertParseOutput('test []', '<r:echo value="test" /> <r:wrap value="test"></r:wrap>');
    }

    public function testThingsThatShouldBeLeftAlone()
    {
        foreach (array(
            ' test="2"="4"',
            '="2"',
            ' / ',
        ) as $middle)
        {
            $this->assertParseIsUnchanged("<r:attr{$middle}>");
            $this->assertParseIsUnchanged("<r:attr{$middle}/>");
        }


        foreach (array(
            '<r:test val>',
            '<r:test val=val>',
            '</r:test />',
            '</r:test arg="val">',
        ) as $test)
        {
            $this->assertParseIsUnchanged($test);
        }

        $this->assertParseOutput('[</r:echo />]', '<r:wrap></r:echo /></r:wrap>');
    }


    public function testTagsInsideHtmlTags()
    {
        $this->assertParseOutput('<div class="xzibit">tags in yo tags</div>',
            '<div class="<r:reverse>tibizx</r:reverse>">tags in yo tags</div>');
    }

    public function testParseDoubleTags()
    {
        $this->assertParseOutput(strrev('test'), '<r:reverse>test</r:reverse>');
        $this->assertParseOutput('tset TEST', '<r:reverse>test</r:reverse> <r:capitalize>test</r:capitalize>');
    }
    
    public function testParseTagNesting()
    {
        /* TOCOPY */
    }

    public function testParseTagNesting2()
    {
        $this->context->defineTag('parent', function ($t) {
            return $t->expand();
        });
        $this->context->defineTag('parent:child', function ($t) {
            return $t->expand();
        });
        $this->context->defineTag('content', function ($t) {
            return $t->getNesting();
        });
        $this->assertParseOutput('parent:child:content', '<r:parent><r:child:content /></r:parent>');
    }

    public function testParseTag_BindingDoMissing()
    {
        $this->setExpectedException('Radius\\UndefinedTagError');
        $this->context->defineTag('test', function($t) { $t->missing(); });
        $this->parser->parse('<r:test />');
    }

    public function testParseChirpyBird()
    {
        $this->assertParseOutput('<:', '<:');
    }
  
    public function testAccessingTagAttributesThroughTagIndexer()
    {
        $this->context->defineTag('test', function($t) {
            if (!isset($t['name'])) {
                $t['name'] = 'Nameless';
            }
            return "Hello {$t['name']}!";
        });

        $this->assertParseOutput('Hello John!', '<r:test name="John" />');
        $this->assertParseOutput('Hello Nameless!', '<r:test />');
    }
  
    public function testParseTag_BindingRenderTagWithBlock()
    {
        $this->context->defineTags(array(
            'test'  => function ($t) { return "Hello {$t->expand()}!"; },
            'hello' => function ($t) { return $t->render('test', array(), function () use ($t) { return $t->expand(); }); },
        ));
        
        $this->assertParseOutput('Hello John!', '<r:hello>John</r:hello>');
    }

    public function testTagLocals()
    {
        $this->context->defineTags(array(
            'outer'     => function ($t) { $t->locals->var = 'outer'; return $t->expand(); },
            'outer:inner' => function ($t) { $t->locals->var = 'inner'; return $t->expand(); },
            'outer:var' => function ($t) { return $t->locals->var; },
        ));

        $this->assertParseOutput('outer',   '<r:outer><r:var /></r:outer>');
        $this->assertParseOutput('outer:inner:outer',
                                            '<r:outer><r:var />:<r:inner><r:var /></r:inner>:<r:var /></r:outer>');
        $this->assertParseOutput('outer:inner:outer:inner:outer',
                                            "<r:outer><r:var />:<r:inner><r:var />:<r:outer><r:var /></r:outer>:<r:var /></r:inner>:<r:var /></r:outer>");
        $this->assertParseOutput('outer',   "<r:outer:var />");
    }
  
    public function testTagGlobals()
    {
        $this->context->defineTags(array(
            'set'   => function($t) { $t->globals->var = $t->getAttr('value'); },
            'var'   => function($t) { return $t->globals->var; },
        ));

        $this->assertParseOutput('  true  false', '<r:var /> <r:set value="true" /> <r:var /> <r:set value="false" /> <r:var />');
    }

    public function testParseTag_BindingRenderTag()
    {
        $this->context->defineTag('test', function($t) {
            return "Hello {$t->getAttr('name')}!";
        });
        $this->context->defineTag('hello', function($t) {
            return $t->render('test', $t->getAttrs());
        });
        $this->assertParseOutput('Hello John!', '<r:hello name="John" />');
    }

    public function testTextMixedInWithTags()
    {
        $this->context->defineTag('test', function($t) { return $t->name; });
        $this->assertParseOutput('test test', 'test <r:test />');
    }

    public function testParseFailOnMissingEndTag()
    {
        $this->setExpectedException('Radius\\MissingEndTagError');
        $this->parser->parse('<r:open>');
    }

    public function testParseFailOnWrongEndTag()
    {
        $this->setExpectedException('Radius\\WrongEndTagError');
        $this->parser->parse('<r:open></r:not_open>');
    }
  
    public function testParseWithOtherRadiusLikeTags()
    {
        $this->context->defineTag('hello', function() { return 'hello'; });
        $this->parser = new Parser($this->context, array('tag_prefix' => 'ralph'));
        $this->assertEquals('<r:ralph:hello />', $this->parser->parse('<r:ralph:hello />'));
    }
  
    public function testParseWithOtherNamespaces()
    {
        $parser = new Parser($this->context, array('tag_prefix' => 'r'));
        $this->assertEquals('<fb:test>hello world</fb:test>', $this->parser->parse('<fb:test>hello world</fb:test>'));
    }

    protected function assertParseIsUnchanged($input, $message = null)
    {
        $this->assertParseOutput($input, $input, "$message with input `$input'");
    }

    protected function assertParseOutput($output, $input, $message = null)
    {
        $r = $this->parser->parse($input);
        $this->assertEquals($output, $r, $message);
    }

    protected function attrFromArray($attr, $q = '"')
    {
        $attrStr = '';
        foreach ($attr as $k => $v) {
            $attrStr .= "{$k} = {$q}{$v}{$q} ";
        }
        return $attrStr;
    }
}
