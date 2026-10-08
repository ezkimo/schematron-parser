<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\SumFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileSumFunctionTest extends TestCase
{
    public function testSumFunctionCompilesPurePathArgumentAsNodeSet(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);

        $functions = [
            SumFunction::class,
        ];

        $compiler = new XPathAstCompiler($functions);

        $expression = 'sum(ram:Amount)';
        $node = $parser->parse($expression);
        $php = $compiler->compile($node, '$context');

        $this->assertIsString($php);
        $this->assertNotEmpty($php);
        $this->assertStringContainsString('$xpath->evaluate("ram:Amount", $context)', $php);
        $this->assertStringNotContainsString('string(ram:Amount)', $php);
        $this->assertStringContainsString('\DOMNodeList', $php);
        $this->assertStringContainsString('$node->textContent', $php);
    }
}
