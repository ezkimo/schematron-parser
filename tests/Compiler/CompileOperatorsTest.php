<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileOperatorsTest extends TestCase
{
    public function testCompilesDivOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('10 div 2');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('(float) (10) / (float) (2)', $php);
    }

    public function testCompilesPlusOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('1 + 2');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('(float) (1) + (float) (2)', $php);
    }

    public function testCompilesMinusOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('2 - 1');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('(float) (2) - (float) (1)', $php);
    }

    public function testCompilesMultipleOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('2 * 8');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('(float) (2) * (float) (8)', $php);
    }

    public function testCompileModulusOperator(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('4 mod 2');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('(float) (4) % (float) (2)', $php);
    }
}