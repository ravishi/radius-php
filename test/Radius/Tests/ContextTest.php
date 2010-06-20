<?php

namespace Radius\Tests;

use Radius\Context;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    protected
        $context;

    public function setUp()
    {
        $this->context = new Context;
    }

    public function testInitialize()
    {
        $this->context = new Context;
    }

    public function testRenderTag()
    {
        $this->context->defineTag('hello', function ($tag) {
            return "Hello {$tag->getAttr('name', 'World')}!";
        });

        $this->assertRenderTagOutput('Hello World!', 'hello');
        $this->assertRenderTagOutput('Hello John!', 'hello', array('name' => 'John'));
    }

    public function testRenderTag_UndefinedTag()
    {
        $this->setExpectedException('Radius\\Error\\UndefinedTagError');
        $this->context->renderTag('undefined_tag');
    }

    protected function assertRenderTagOutput($output, $name, $options = array(), $block = null)
    {
        $this->assertEquals($output, $this->context->renderTag($name, $options, $block));
    }
}
