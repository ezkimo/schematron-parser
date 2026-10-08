<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\CountFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileCountFunctionTest extends TestCase
{
    public function testCompilesCountFunction(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        
        $functions = [
            CountFunction::class,
        ];

        $compiler = new XPathAstCompiler($functions);

        $node = $parser->parse('count(ram:Amount)');
        $php = $compiler->compile($node, '$context');

        $this->assertIsString($php);
        $this->assertStringContainsString('$xpath->evaluate("ram:Amount", $context)', $php);
        $this->assertStringNotContainsString('string(ram:Amount)', $php);
        $this->assertStringContainsString('\DOMNodeList', $php);
        $this->assertStringContainsString('->length', $php);
    }
}
