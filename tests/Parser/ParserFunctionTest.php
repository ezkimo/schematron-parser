<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Parser;

use MMNewmedia\Schematron\Ast\FunctionNode;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class ParserFunctionTest extends TestCase
{
    public function testParsesSumFunction(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $node = $parser->parse('sum(ram:Amount)');

        $this->assertInstanceOf(FunctionNode::class, $node);
        $this->assertSame('sum', $node->name);
        $this->assertCount(1, $node->arguments);
    }
}