<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Parser;

use MMNewmedia\Schematron\Ast\OperatorNode;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class ParserOperatorTest extends TestCase
{
    public function testParsesDivOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $node = $parser->parse('10 div 2');

        $this->assertInstanceOf(OperatorNode::class, $node);
        $this->assertSame('div', $node->operator);
    }
}