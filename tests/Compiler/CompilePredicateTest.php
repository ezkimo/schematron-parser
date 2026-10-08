<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompilePredicateTest extends TestCase
{
    public function testCompilesSimplePredicate(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('ram:Amount[. = 100]');
        $php = $compiler->compile($node, '$context');

        $this->assertStringContainsString('[', $php);
        $this->assertStringContainsString('100', $php);
    }
}