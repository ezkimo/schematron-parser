<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileParentAxisTest extends TestCase
{
    public function testCompilesParentAxis(): void
    {
        $tokenizer = new XPathTokenizer();
        $parser = new XPathAstParser($tokenizer);
        $compiler = new XPathAstCompiler([]);

        $node = $parser->parse('..');
        $php = $compiler->compile($node, '$context');

        $this->assertSame(
            "\$xpath->evaluate(\"string(..)\", \$context)",
            $php
        );
    }
}