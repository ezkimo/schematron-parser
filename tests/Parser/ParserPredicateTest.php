<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Parser;

use MMNewmedia\Schematron\Ast\PredicateNode;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class ParserPredicateTest extends TestCase
{
    public function testParsesPredicate(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);

        /** @var PredicateNode $node */
        $node = $parser->parse('ram:Amount[. = 100]');

        $this->assertInstanceOf(PredicateNode::class, $node);
    }
}