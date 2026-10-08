<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\RoundFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileRoundFunctionTest extends TestCase
{
    public function testCompilesRoundFunction(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        
        $functions = [
            RoundFunction::class,
        ];

        $compiler = new XPathAstCompiler($functions);
        
        $node = $parser->parse('round(5.7)');
        $php = $compiler->compile($node, '$context');

        $this->assertSame('round((float) 5.7)', $php);
    }
}