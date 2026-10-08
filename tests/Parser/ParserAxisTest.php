<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Parser;

use MMNewmedia\Schematron\Ast\AxisNode;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class ParserAxisTest extends TestCase
{
    public function testParsesSelfAxis(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $node = $parser->parse('.');

        $this->assertInstanceOf(AxisNode::class, $node);
        $this->assertSame('self', $node->axis);
        $this->assertNull($node->name);
    }

    public function testParsesParentAxis(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $node = $parser->parse('..');

        $this->assertInstanceOf(AxisNode::class, $node);
        $this->assertSame('parent', $node->axis);
        $this->assertNull($node->name);
    }
}